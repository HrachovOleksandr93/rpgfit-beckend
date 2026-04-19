<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\PsychProfile;

use App\Application\PsychProfile\Service\CheckInService;
use App\Application\PsychProfile\Service\StatusAssignmentService;
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
}
