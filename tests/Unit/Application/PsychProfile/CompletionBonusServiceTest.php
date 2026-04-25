<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\PsychProfile;

use App\Application\PsychProfile\Service\CompletionBonusService;
use App\Domain\Config\Entity\GameSetting;
use App\Domain\User\Entity\User;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Covers CompletionBonusService:
 *  - happy path: eligible user gets the full +10% bump.
 *  - idempotency: second call on same day returns baseline.
 *  - no marker: returns baseline.
 *  - weekly cap: 5 applied markers in the last 7 days block the 6th.
 */
final class CompletionBonusServiceTest extends TestCase
{
    private function makeUser(): User
    {
        // User::__construct() assigns a fresh UUID — no reflection needed.
        $user = new User();
        $user->setLogin('hero@example.com');
        $user->setPassword('hash');

        return $user;
    }

    private function makeMarker(string $key, \DateTimeImmutable $date): GameSetting
    {
        $setting = new GameSetting();
        $setting->setCategory('psych')
            ->setKey($key)
            ->setValue($date->format(\DateTimeInterface::ATOM));

        return $setting;
    }

    /**
     * Build a game-settings stub that returns the markers in $markers
     * map (keyed by setting key) and null for anything else.
     *
     * @param array<string, GameSetting> $markers
     */
    private function makeSettings(array $markers): GameSettingRepository
    {
        $settings = $this->createMock(GameSettingRepository::class);
        $settings->method('findByKey')->willReturnCallback(
            static fn (string $key) => $markers[$key] ?? null,
        );

        return $settings;
    }

    public function testApplyIfEligibleReturnsBaseWhenNoMarker(): void
    {
        $user = $this->makeUser();
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);

        $settings = $this->makeSettings([]);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');
        $em->expects(self::never())->method('flush');

        $service = new CompletionBonusService($settings, $em, new NullLogger());

        self::assertSame(100, $service->applyIfEligible($user, $today, 100));
    }

    public function testApplyIfEligibleAppliesTenPercentWhenEligible(): void
    {
        $user = $this->makeUser();
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);

        $markerKey = CompletionBonusService::markerKey($user, $today);
        $settings = $this->makeSettings([
            $markerKey => $this->makeMarker($markerKey, $today),
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        // Expect ONE persist (the "applied" marker) and ONE flush.
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $service = new CompletionBonusService($settings, $em, new NullLogger());

        self::assertSame(110, $service->applyIfEligible($user, $today, 100));
    }

    public function testApplyIfEligibleIsIdempotentWhenAlreadyApplied(): void
    {
        $user = $this->makeUser();
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);

        $markerKey = CompletionBonusService::markerKey($user, $today);
        $appliedKey = CompletionBonusService::appliedKey($user, $today);

        $settings = $this->makeSettings([
            $markerKey => $this->makeMarker($markerKey, $today),
            $appliedKey => $this->makeMarker($appliedKey, $today),
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $service = new CompletionBonusService($settings, $em, new NullLogger());

        self::assertSame(100, $service->applyIfEligible($user, $today, 100));
    }

    public function testWeeklyCapBlocksSixthDayWithinSevenDayWindow(): void
    {
        $user = $this->makeUser();
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);

        // 5 prior applied markers at d-1..d-5 (already at the cap).
        // Adding today would be the 6th — must be blocked.
        $priorMarkers = [];
        for ($i = 1; $i <= 5; ++$i) {
            $day = $today->modify(sprintf('-%d day', $i));
            $key = CompletionBonusService::appliedKey($user, $day);
            $priorMarkers[$key] = $this->makeMarker($key, $day);
        }

        // Today has the eligibility marker but NOT yet applied.
        $markerKey = CompletionBonusService::markerKey($user, $today);
        $priorMarkers[$markerKey] = $this->makeMarker($markerKey, $today);

        $settings = $this->makeSettings($priorMarkers);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $service = new CompletionBonusService($settings, $em, new NullLogger());

        self::assertSame(100, $service->applyIfEligible($user, $today, 100));
    }

    public function testFourPriorMarkersAllowTodayToFire(): void
    {
        $user = $this->makeUser();
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);

        $priorMarkers = [];
        for ($i = 1; $i <= 4; ++$i) {
            $day = $today->modify(sprintf('-%d day', $i));
            $key = CompletionBonusService::appliedKey($user, $day);
            $priorMarkers[$key] = $this->makeMarker($key, $day);
        }

        $markerKey = CompletionBonusService::markerKey($user, $today);
        $priorMarkers[$markerKey] = $this->makeMarker($markerKey, $today);

        $settings = $this->makeSettings($priorMarkers);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $service = new CompletionBonusService($settings, $em, new NullLogger());

        self::assertSame(110, $service->applyIfEligible($user, $today, 100));
    }

    public function testMarkEligibilityIsIdempotent(): void
    {
        $user = $this->makeUser();
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);

        $markerKey = CompletionBonusService::markerKey($user, $today);
        $settings = $this->makeSettings([
            $markerKey => $this->makeMarker($markerKey, $today),
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');
        $em->expects(self::never())->method('flush');

        $service = new CompletionBonusService($settings, $em, new NullLogger());
        $service->markEligibility($user, $today);
    }

    public function testIsEligibleRespectsMarkerAndCap(): void
    {
        $user = $this->makeUser();
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);

        $markerKey = CompletionBonusService::markerKey($user, $today);
        $settings = $this->makeSettings([
            $markerKey => $this->makeMarker($markerKey, $today),
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $service = new CompletionBonusService($settings, $em, new NullLogger());

        self::assertTrue($service->isEligible($user, $today));
    }
}
