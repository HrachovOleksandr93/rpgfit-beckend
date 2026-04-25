<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\PsychProfile;

use App\Application\PsychProfile\Service\PhysicalStateService;
use App\Application\PsychProfile\Service\PsychWorkoutAdapterService;
use App\Domain\PsychProfile\Entity\PhysicalStateAnswer;
use App\Domain\PsychProfile\Entity\PsychCheckIn;
use App\Domain\PsychProfile\Entity\PsychUserProfile;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\User\Entity\User;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\PsychProfile\Repository\PsychCheckInRepository;
use App\Infrastructure\PsychProfile\Repository\PsychUserProfileRepository;
use PHPUnit\Framework\TestCase;

/**
 * Covers every matrix row in spec §1.3 + the asymmetric raise predicate
 * + the feature-off / opted-out baseline path.
 */
final class PsychWorkoutAdapterServiceTest extends TestCase
{
    private function makeUser(): User
    {
        $user = new User();
        $user->setLogin('hero@example.com');
        $user->setPassword('hash');

        return $user;
    }

    private function makeProfile(PsychStatus $status, bool $optedIn = true): PsychUserProfile
    {
        $profile = new PsychUserProfile();
        $profile->setUser($this->makeUser());
        $profile->setCurrentStatus($status);
        $profile->setFeatureOptedIn($optedIn);

        return $profile;
    }

    private function makeAnswer(int $rpe): PhysicalStateAnswer
    {
        $answer = new PhysicalStateAnswer();
        $answer->setRpeScore($rpe);

        return $answer;
    }

    /**
     * @param list<PsychCheckIn> $recentRows rows returned by findInRange()
     */
    private function buildService(
        PsychUserProfile $profile,
        ?PhysicalStateAnswer $latestAnswer,
        array $recentRows = [],
    ): PsychWorkoutAdapterService {
        $profiles = $this->createMock(PsychUserProfileRepository::class);
        $profiles->method('findByUser')->willReturn($profile);

        $checkIns = $this->createMock(PsychCheckInRepository::class);
        $checkIns->method('findInRange')->willReturn($recentRows);

        $physical = $this->createMock(PhysicalStateService::class);
        $physical->method('getLatest')->willReturn($latestAnswer);

        $settings = $this->createMock(GameSettingRepository::class);
        // Force hardcoded fallback — don't depend on seed.
        $settings->method('findByKey')->willReturn(null);

        return new PsychWorkoutAdapterService($profiles, $checkIns, $physical, $settings);
    }

    public function testChargedFreshRaisesLoadWhenPredicateClean(): void
    {
        $profile = $this->makeProfile(PsychStatus::CHARGED);
        $service = $this->buildService($profile, $this->makeAnswer(2), recentRows: []);

        $adaptation = $service->adapt($this->makeUser());

        self::assertEqualsWithDelta(0.10, $adaptation->intensityDelta, 1e-9);
        self::assertEqualsWithDelta(0.10, $adaptation->volumeDelta, 1e-9);
        self::assertSame('new-challenge', $adaptation->focus);
        self::assertNull($adaptation->warningCopy);
    }

    public function testChargedFreshFallsBackWhenWearyInLast3Days(): void
    {
        $profile = $this->makeProfile(PsychStatus::CHARGED);

        $weary = new PsychCheckIn();
        $weary->setAssignedStatus(PsychStatus::WEARY)
            ->setCheckedInOn((new \DateTimeImmutable('-2 day'))->setTime(0, 0, 0));

        $service = $this->buildService($profile, $this->makeAnswer(1), recentRows: [$weary]);

        $adaptation = $service->adapt($this->makeUser());

        self::assertEqualsWithDelta(0.0, $adaptation->intensityDelta, 1e-9);
        self::assertEqualsWithDelta(0.0, $adaptation->volumeDelta, 1e-9);
        self::assertSame('new-challenge', $adaptation->focus);
    }

    public function testChargedWithHighRpeStaysBaseline(): void
    {
        $profile = $this->makeProfile(PsychStatus::CHARGED);
        $service = $this->buildService($profile, $this->makeAnswer(4), recentRows: []);

        $adaptation = $service->adapt($this->makeUser());

        self::assertEqualsWithDelta(0.0, $adaptation->intensityDelta, 1e-9);
        self::assertSame('new-challenge', $adaptation->focus);
    }

    public function testSteadyBaseline(): void
    {
        $profile = $this->makeProfile(PsychStatus::STEADY);
        $service = $this->buildService($profile, null);

        $adaptation = $service->adapt($this->makeUser());

        self::assertEqualsWithDelta(0.0, $adaptation->intensityDelta, 1e-9);
        self::assertEqualsWithDelta(0.0, $adaptation->volumeDelta, 1e-9);
        self::assertSame('baseline', $adaptation->focus);
        self::assertNull($adaptation->warningCopy);
    }

    public function testDormantReducesLoadAndWarns(): void
    {
        $profile = $this->makeProfile(PsychStatus::DORMANT);
        $service = $this->buildService($profile, null);

        $adaptation = $service->adapt($this->makeUser());

        self::assertEqualsWithDelta(-0.20, $adaptation->intensityDelta, 1e-9);
        self::assertEqualsWithDelta(-0.15, $adaptation->volumeDelta, 1e-9);
        self::assertEqualsWithDelta(-0.20, $adaptation->durationDelta, 1e-9);
        self::assertSame('mobility', $adaptation->focus);
        self::assertNotNull($adaptation->warningCopy);
    }

    public function testWearyWithLowRpeGetsModerateReduction(): void
    {
        $profile = $this->makeProfile(PsychStatus::WEARY);
        $service = $this->buildService($profile, $this->makeAnswer(2));

        $adaptation = $service->adapt($this->makeUser());

        self::assertEqualsWithDelta(-0.25, $adaptation->intensityDelta, 1e-9);
        self::assertEqualsWithDelta(-0.25, $adaptation->volumeDelta, 1e-9);
        self::assertEqualsWithDelta(-0.20, $adaptation->durationDelta, 1e-9);
        self::assertSame('recovery', $adaptation->focus);
    }

    public function testWearyWithHighRpeGetsDeepReduction(): void
    {
        $profile = $this->makeProfile(PsychStatus::WEARY);
        $service = $this->buildService($profile, $this->makeAnswer(5));

        $adaptation = $service->adapt($this->makeUser());

        self::assertEqualsWithDelta(-0.35, $adaptation->intensityDelta, 1e-9);
        self::assertEqualsWithDelta(-0.35, $adaptation->volumeDelta, 1e-9);
        self::assertEqualsWithDelta(-0.30, $adaptation->durationDelta, 1e-9);
        self::assertSame('recovery-only', $adaptation->focus);
    }

    public function testWearyWithoutRpeFallsToModerateRow(): void
    {
        // No Q4 — should pick the unconstrained WEARY row.
        $profile = $this->makeProfile(PsychStatus::WEARY);
        $service = $this->buildService($profile, null);

        $adaptation = $service->adapt($this->makeUser());

        self::assertEqualsWithDelta(-0.25, $adaptation->intensityDelta, 1e-9);
        self::assertSame('recovery', $adaptation->focus);
    }

    public function testScatteredReducesLoadWithFocusWarning(): void
    {
        $profile = $this->makeProfile(PsychStatus::SCATTERED);
        $service = $this->buildService($profile, null);

        $adaptation = $service->adapt($this->makeUser());

        self::assertEqualsWithDelta(-0.15, $adaptation->intensityDelta, 1e-9);
        self::assertSame('focus', $adaptation->focus);
        self::assertNotNull($adaptation->warningCopy);
    }

    public function testOptedOutProfileReturnsBaseline(): void
    {
        $profile = $this->makeProfile(PsychStatus::CHARGED, optedIn: false);
        $service = $this->buildService($profile, $this->makeAnswer(1));

        $adaptation = $service->adapt($this->makeUser());

        self::assertEqualsWithDelta(0.0, $adaptation->intensityDelta, 1e-9);
        self::assertEqualsWithDelta(0.0, $adaptation->volumeDelta, 1e-9);
        self::assertNull($adaptation->warningCopy);
    }

    public function testMissingProfileReturnsBaseline(): void
    {
        $profiles = $this->createMock(PsychUserProfileRepository::class);
        $profiles->method('findByUser')->willReturn(null);

        $checkIns = $this->createMock(PsychCheckInRepository::class);
        $physical = $this->createMock(PhysicalStateService::class);
        $settings = $this->createMock(GameSettingRepository::class);

        $service = new PsychWorkoutAdapterService($profiles, $checkIns, $physical, $settings);

        $adaptation = $service->adapt($this->makeUser());

        self::assertEqualsWithDelta(0.0, $adaptation->intensityDelta, 1e-9);
        self::assertEqualsWithDelta(0.0, $adaptation->volumeDelta, 1e-9);
        self::assertEqualsWithDelta(0.0, $adaptation->durationDelta, 1e-9);
    }

    public function testAsymmetricPredicateBlocksRaiseOnScatteredInWindow(): void
    {
        $profile = $this->makeProfile(PsychStatus::CHARGED);

        $scattered = new PsychCheckIn();
        $scattered->setAssignedStatus(PsychStatus::SCATTERED)
            ->setCheckedInOn((new \DateTimeImmutable('-1 day'))->setTime(0, 0, 0));

        $service = $this->buildService($profile, $this->makeAnswer(1), recentRows: [$scattered]);

        self::assertFalse($service->asymmetricRaiseAllowed($this->makeUser(), 1));
    }

    public function testAsymmetricPredicateBlocksRaiseOnHighRpe(): void
    {
        $profile = $this->makeProfile(PsychStatus::CHARGED);
        $service = $this->buildService($profile, $this->makeAnswer(3), recentRows: []);

        self::assertFalse($service->asymmetricRaiseAllowed($this->makeUser(), 3));
    }

    public function testAsymmetricPredicateAllowsWhenCleanHistoryAndLowRpe(): void
    {
        $profile = $this->makeProfile(PsychStatus::CHARGED);

        $steady = new PsychCheckIn();
        $steady->setAssignedStatus(PsychStatus::STEADY)
            ->setCheckedInOn((new \DateTimeImmutable('-2 day'))->setTime(0, 0, 0));

        $service = $this->buildService($profile, $this->makeAnswer(2), recentRows: [$steady]);

        self::assertTrue($service->asymmetricRaiseAllowed($this->makeUser(), 2));
    }
}
