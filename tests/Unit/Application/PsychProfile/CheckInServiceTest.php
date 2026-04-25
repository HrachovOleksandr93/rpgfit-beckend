<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\PsychProfile;

use App\Application\PsychProfile\Service\CheckInService;
use App\Application\PsychProfile\Service\CompletionBonusService;
use App\Application\PsychProfile\Service\PhysicalStateService;
use App\Application\PsychProfile\Service\StatusAssignmentService;
use App\Domain\PsychProfile\Entity\PhysicalStateAnswer;
use App\Domain\PsychProfile\Entity\PsychCheckIn;
use App\Domain\PsychProfile\Entity\PsychUserProfile;
use App\Domain\PsychProfile\Enum\MoodQuadrant;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\PsychProfile\Enum\UserIntent;
use App\Domain\User\Entity\User;
use App\Infrastructure\PsychProfile\Repository\PsychCheckInRepository;
use App\Infrastructure\PsychProfile\Repository\PsychUserProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Covers the three write paths of CheckInService:
 *   1. Happy path — answered check-in, consecutive skips reset to 0.
 *   2. Skip inherits the previous status and increments the counter.
 *   3. Seventh skip forces STEADY even when the previous status was Weary.
 */
final class CheckInServiceTest extends TestCase
{
    private function makeUser(): User
    {
        $user = new User();
        $user->setLogin('hero@example.com');
        $user->setPassword('hash');

        return $user;
    }

    private function makeProfile(PsychStatus $status, int $skips): PsychUserProfile
    {
        $profile = new PsychUserProfile();
        $profile->setUser($this->makeUser());
        $profile->setCurrentStatus($status);
        $profile->setConsecutiveSkips($skips);

        return $profile;
    }

    private function buildService(
        StatusAssignmentService $assignment,
        PsychCheckInRepository $checkInRepo,
        PsychUserProfileRepository $profileRepo,
    ): CheckInService {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::atLeastOnce())->method('flush');

        return new CheckInService(
            $assignment,
            $checkInRepo,
            $profileRepo,
            $em,
            new NullLogger(),
        );
    }

    public function testAnsweredCheckInAssignsStatusAndResetsSkipCounter(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile(PsychStatus::WEARY, 3);

        $assignment = $this->createMock(StatusAssignmentService::class);
        $assignment->expects(self::once())
            ->method('assign')
            ->with(MoodQuadrant::ENERGIZED, 4, UserIntent::PUSH)
            ->willReturn(PsychStatus::CHARGED);

        $checkInRepo = $this->createMock(PsychCheckInRepository::class);
        $checkInRepo->method('findForUserOnDate')->willReturn(null);

        $profileRepo = $this->createMock(PsychUserProfileRepository::class);
        $profileRepo->method('findOrCreateForUser')->with($user)->willReturn($profile);

        $service = $this->buildService($assignment, $checkInRepo, $profileRepo);

        $checkIn = $service->checkIn($user, MoodQuadrant::ENERGIZED, 4, UserIntent::PUSH, false);

        self::assertInstanceOf(PsychCheckIn::class, $checkIn);
        self::assertSame(PsychStatus::CHARGED, $checkIn->getAssignedStatus());
        self::assertSame(PsychStatus::CHARGED, $profile->getCurrentStatus());
        self::assertSame(0, $profile->getConsecutiveSkips());
        self::assertFalse($checkIn->isSkipped());
        self::assertSame(MoodQuadrant::ENERGIZED, $checkIn->getMoodQuadrant());
        self::assertSame(4, $checkIn->getEnergyLevel());
        self::assertSame(UserIntent::PUSH, $checkIn->getIntent());
    }

    public function testSkipInheritsPreviousStatusAndIncrementsCounter(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile(PsychStatus::CHARGED, 2);

        $assignment = $this->createMock(StatusAssignmentService::class);
        $assignment->expects(self::never())->method('assign');

        $checkInRepo = $this->createMock(PsychCheckInRepository::class);
        $checkInRepo->method('findForUserOnDate')->willReturn(null);

        $profileRepo = $this->createMock(PsychUserProfileRepository::class);
        $profileRepo->method('findOrCreateForUser')->willReturn($profile);

        $service = $this->buildService($assignment, $checkInRepo, $profileRepo);

        $checkIn = $service->checkIn($user, null, null, null, true);

        self::assertTrue($checkIn->isSkipped());
        self::assertNull($checkIn->getMoodQuadrant());
        self::assertNull($checkIn->getEnergyLevel());
        self::assertNull($checkIn->getIntent());
        self::assertSame(PsychStatus::CHARGED, $checkIn->getAssignedStatus());
        self::assertSame(3, $profile->getConsecutiveSkips());
    }

    public function testSeventhSkipForcesSteadyRegardlessOfPreviousStatus(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile(PsychStatus::WEARY, 6);

        $assignment = $this->createMock(StatusAssignmentService::class);
        $assignment->expects(self::never())->method('assign');

        $checkInRepo = $this->createMock(PsychCheckInRepository::class);
        $checkInRepo->method('findForUserOnDate')->willReturn(null);

        $profileRepo = $this->createMock(PsychUserProfileRepository::class);
        $profileRepo->method('findOrCreateForUser')->willReturn($profile);

        $service = $this->buildService($assignment, $checkInRepo, $profileRepo);

        $checkIn = $service->checkIn($user, null, null, null, true);

        self::assertTrue($checkIn->isSkipped());
        self::assertSame(PsychStatus::STEADY, $checkIn->getAssignedStatus());
        self::assertSame(PsychStatus::STEADY, $profile->getCurrentStatus());
        self::assertSame(7, $profile->getConsecutiveSkips());
    }

    public function testSecondCallOnSameDayReusesExistingRow(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile(PsychStatus::STEADY, 0);

        $existing = new PsychCheckIn();
        $existing->setUser($user)
            ->setAssignedStatus(PsychStatus::CHARGED)
            ->setCheckedInOn((new \DateTimeImmutable())->setTime(0, 0, 0));

        $assignment = $this->createMock(StatusAssignmentService::class);
        $assignment->expects(self::never())->method('assign');

        $checkInRepo = $this->createMock(PsychCheckInRepository::class);
        $checkInRepo->method('findForUserOnDate')->willReturn($existing);

        $profileRepo = $this->createMock(PsychUserProfileRepository::class);
        $profileRepo->method('findOrCreateForUser')->willReturn($profile);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $service = new CheckInService(
            $assignment,
            $checkInRepo,
            $profileRepo,
            $em,
            new NullLogger(),
        );

        self::assertSame($existing, $service->checkIn($user, null, null, null, true));
    }

    // =====================================================================
    // Psych v2 (spec 2026-04-19) — Q4 merge + completion bonus eligibility
    // =====================================================================

    public function testRpeScoreProvidedRecordsFreshAnswerAndLinksIt(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile(PsychStatus::STEADY, 0);

        $answer = new PhysicalStateAnswer();
        $answer->setUser($user)->setRpeScore(3);

        $assignment = $this->createMock(StatusAssignmentService::class);
        $assignment->method('assign')->willReturn(PsychStatus::STEADY);

        $checkInRepo = $this->createMock(PsychCheckInRepository::class);
        $checkInRepo->method('findForUserOnDate')->willReturn(null);

        $profileRepo = $this->createMock(PsychUserProfileRepository::class);
        $profileRepo->method('findOrCreateForUser')->willReturn($profile);

        $physical = $this->createMock(PhysicalStateService::class);
        $physical->expects(self::once())
            ->method('record')
            ->with($user, null, 3)
            ->willReturn($answer);
        $physical->expects(self::never())->method('getLatestInWindow');

        $bonus = $this->createMock(CompletionBonusService::class);
        $bonus->expects(self::once())->method('markEligibility')->with($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist');
        $em->method('flush');

        $service = new CheckInService(
            $assignment,
            $checkInRepo,
            $profileRepo,
            $em,
            new NullLogger(),
            $physical,
            $bonus,
        );

        $checkIn = $service->checkIn(
            $user,
            MoodQuadrant::NEUTRAL,
            3,
            UserIntent::MAINTAIN,
            false,
            3,
        );

        self::assertSame($answer, $checkIn->getPhysicalStateAnswer());
    }

    public function testNoRpeButRecentAnswerInWindowIsMerged(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile(PsychStatus::STEADY, 0);

        $answer = new PhysicalStateAnswer();
        $answer->setUser($user)->setRpeScore(4);

        $assignment = $this->createMock(StatusAssignmentService::class);
        $assignment->method('assign')->willReturn(PsychStatus::STEADY);

        $checkInRepo = $this->createMock(PsychCheckInRepository::class);
        $checkInRepo->method('findForUserOnDate')->willReturn(null);

        $profileRepo = $this->createMock(PsychUserProfileRepository::class);
        $profileRepo->method('findOrCreateForUser')->willReturn($profile);

        $physical = $this->createMock(PhysicalStateService::class);
        $physical->expects(self::never())->method('record');
        $physical->expects(self::once())
            ->method('getLatestInWindow')
            ->willReturn($answer);

        $bonus = $this->createMock(CompletionBonusService::class);
        $bonus->expects(self::once())->method('markEligibility');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist');
        $em->method('flush');

        $service = new CheckInService(
            $assignment,
            $checkInRepo,
            $profileRepo,
            $em,
            new NullLogger(),
            $physical,
            $bonus,
        );

        $checkIn = $service->checkIn(
            $user,
            MoodQuadrant::AT_EASE,
            3,
            UserIntent::MAINTAIN,
            false,
            null,
        );

        self::assertSame($answer, $checkIn->getPhysicalStateAnswer());
    }

    public function testSkippedCheckInDoesNotTriggerBonusOrQ4Lookup(): void
    {
        $user = $this->makeUser();
        $profile = $this->makeProfile(PsychStatus::STEADY, 0);

        $assignment = $this->createMock(StatusAssignmentService::class);

        $checkInRepo = $this->createMock(PsychCheckInRepository::class);
        $checkInRepo->method('findForUserOnDate')->willReturn(null);

        $profileRepo = $this->createMock(PsychUserProfileRepository::class);
        $profileRepo->method('findOrCreateForUser')->willReturn($profile);

        $physical = $this->createMock(PhysicalStateService::class);
        $physical->expects(self::never())->method('record');
        $physical->expects(self::never())->method('getLatestInWindow');

        $bonus = $this->createMock(CompletionBonusService::class);
        $bonus->expects(self::never())->method('markEligibility');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist');
        $em->method('flush');

        $service = new CheckInService(
            $assignment,
            $checkInRepo,
            $profileRepo,
            $em,
            new NullLogger(),
            $physical,
            $bonus,
        );

        $checkIn = $service->checkIn($user, null, null, null, true, null);

        self::assertNull($checkIn->getPhysicalStateAnswer());
        self::assertTrue($checkIn->isSkipped());
    }
}
