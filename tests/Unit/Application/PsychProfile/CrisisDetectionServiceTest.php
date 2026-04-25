<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\PsychProfile;

use App\Application\PsychProfile\Service\CrisisDetectionService;
use App\Domain\Config\Entity\GameSetting;
use App\Domain\PsychProfile\Entity\PsychCheckIn;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\User\Entity\User;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\PsychProfile\Repository\PsychCheckInRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Uid\Uuid;

/**
 * Covers the core rolling-7-day detection + the 30-day re-log cooldown.
 */
final class CrisisDetectionServiceTest extends TestCase
{
    private function makeUser(?Uuid $id = null): User
    {
        $user = new User();
        $user->setLogin('hero@example.com');
        $user->setPassword('hash');

        if ($id !== null) {
            $ref = new ReflectionClass(User::class);
            $prop = $ref->getProperty('id');
            $prop->setValue($user, $id);
        }

        return $user;
    }

    private function makeRow(PsychStatus $status, int $daysAgo): PsychCheckIn
    {
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $day = $today->modify(sprintf('-%d day', $daysAgo));
        $row = new PsychCheckIn();
        $row->setAssignedStatus($status)
            ->setCheckedInOn($day);

        return $row;
    }

    public function testFiveOfSevenWearyTriggersLog(): void
    {
        $user = $this->makeUser(Uuid::v4());

        $checkIns = $this->createMock(PsychCheckInRepository::class);
        $checkIns->method('findInRange')->willReturn([
            $this->makeRow(PsychStatus::WEARY, 6),
            $this->makeRow(PsychStatus::WEARY, 5),
            $this->makeRow(PsychStatus::STEADY, 4),
            $this->makeRow(PsychStatus::WEARY, 3),
            $this->makeRow(PsychStatus::SCATTERED, 2),
            $this->makeRow(PsychStatus::STEADY, 1),
            $this->makeRow(PsychStatus::WEARY, 0),
        ]);

        $settings = $this->createMock(GameSettingRepository::class);
        $settings->method('findByKey')->willReturnCallback(static function (string $key) {
            // No threshold/cooldown override; no prior flag record.
            return null;
        });

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with('psych.crisis-pattern', self::callback(static fn (array $ctx) => ($ctx['crisisDays'] ?? 0) === 5));

        $service = new CrisisDetectionService($checkIns, $settings, $em, $logger);
        self::assertTrue($service->hasCrisisPattern($user));
    }

    public function testFourOfSevenDoesNotTrigger(): void
    {
        $user = $this->makeUser(Uuid::v4());

        $checkIns = $this->createMock(PsychCheckInRepository::class);
        $checkIns->method('findInRange')->willReturn([
            $this->makeRow(PsychStatus::WEARY, 6),
            $this->makeRow(PsychStatus::STEADY, 5),
            $this->makeRow(PsychStatus::STEADY, 4),
            $this->makeRow(PsychStatus::WEARY, 3),
            $this->makeRow(PsychStatus::SCATTERED, 2),
            $this->makeRow(PsychStatus::CHARGED, 1),
            $this->makeRow(PsychStatus::WEARY, 0),
        ]);

        $settings = $this->createMock(GameSettingRepository::class);
        $settings->method('findByKey')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $service = new CrisisDetectionService($checkIns, $settings, $em, $logger);
        self::assertFalse($service->hasCrisisPattern($user));
    }

    public function testWithinCooldownSkipsTheLogButStillReturnsTrue(): void
    {
        $userId = Uuid::v4();
        $user = $this->makeUser($userId);

        $checkIns = $this->createMock(PsychCheckInRepository::class);
        // 5 unique-date WEARY days in the 7-day window — dedupe-by-date
        // means we need 5 distinct daysAgo values, not 5 copies of day=0.
        $checkIns->method('findInRange')->willReturn([
            $this->makeRow(PsychStatus::WEARY, 0),
            $this->makeRow(PsychStatus::WEARY, 1),
            $this->makeRow(PsychStatus::WEARY, 2),
            $this->makeRow(PsychStatus::WEARY, 3),
            $this->makeRow(PsychStatus::WEARY, 4),
        ]);

        $recentFlag = (new GameSetting())
            ->setCategory('psych')
            ->setKey(sprintf('psych.crisis_last_flagged_%s', $userId->toRfc4122()))
            ->setValue((new \DateTimeImmutable('-5 day'))->format(\DateTimeInterface::ATOM));

        $settings = $this->createMock(GameSettingRepository::class);
        $settings->method('findByKey')->willReturnCallback(static function (string $key) use ($recentFlag) {
            if ($key === CrisisDetectionService::SETTING_THRESHOLD) {
                return null;
            }
            if ($key === CrisisDetectionService::SETTING_COOLDOWN) {
                return null;
            }
            if (str_starts_with($key, 'psych.crisis_last_flagged_')) {
                return $recentFlag;
            }

            return null;
        });

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $service = new CrisisDetectionService($checkIns, $settings, $em, $logger);
        self::assertTrue($service->hasCrisisPattern($user));
    }

    public function testExpiredCooldownReLogsTheCrisis(): void
    {
        $userId = Uuid::v4();
        $user = $this->makeUser($userId);

        $checkIns = $this->createMock(PsychCheckInRepository::class);
        // 5 unique-date crisis days (same fix as the prior test).
        $checkIns->method('findInRange')->willReturn([
            $this->makeRow(PsychStatus::SCATTERED, 0),
            $this->makeRow(PsychStatus::SCATTERED, 1),
            $this->makeRow(PsychStatus::SCATTERED, 2),
            $this->makeRow(PsychStatus::SCATTERED, 3),
            $this->makeRow(PsychStatus::SCATTERED, 4),
        ]);

        $oldFlag = (new GameSetting())
            ->setCategory('psych')
            ->setKey(sprintf('psych.crisis_last_flagged_%s', $userId->toRfc4122()))
            ->setValue((new \DateTimeImmutable('-60 day'))->format(\DateTimeInterface::ATOM));

        $settings = $this->createMock(GameSettingRepository::class);
        $settings->method('findByKey')->willReturnCallback(static function (string $key) use ($oldFlag) {
            if (str_starts_with($key, 'psych.crisis_last_flagged_')) {
                return $oldFlag;
            }

            return null;
        });

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::atLeastOnce())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $service = new CrisisDetectionService($checkIns, $settings, $em, $logger);
        self::assertTrue($service->hasCrisisPattern($user));
    }

    public function testNoCheckInsMeansNoCrisis(): void
    {
        $user = $this->makeUser(Uuid::v4());

        $checkIns = $this->createMock(PsychCheckInRepository::class);
        $checkIns->method('findInRange')->willReturn([]);

        $settings = $this->createMock(GameSettingRepository::class);
        $settings->method('findByKey')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $service = new CrisisDetectionService($checkIns, $settings, $em, $logger);
        self::assertFalse($service->hasCrisisPattern($user));
    }

    // =====================================================================
    // Psych v2 (spec §1.5) — getWearyStreakDays
    // =====================================================================

    public function testWearyStreakReturnsZeroWhenNoRowsToday(): void
    {
        $user = $this->makeUser(Uuid::v4());
        $checkIns = $this->createMock(PsychCheckInRepository::class);
        $checkIns->method('findInRange')->willReturn([]);

        $service = new CrisisDetectionService(
            $checkIns,
            $this->createMock(GameSettingRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(LoggerInterface::class),
        );

        self::assertSame(0, $service->getWearyStreakDays($user));
    }

    public function testWearyStreakCountsConsecutiveDaysOfWearyOrScattered(): void
    {
        $user = $this->makeUser(Uuid::v4());

        $rows = [
            $this->makeRow(PsychStatus::WEARY, 4),
            $this->makeRow(PsychStatus::SCATTERED, 3),
            $this->makeRow(PsychStatus::WEARY, 2),
            $this->makeRow(PsychStatus::WEARY, 1),
            $this->makeRow(PsychStatus::WEARY, 0),
        ];

        $checkIns = $this->createMock(PsychCheckInRepository::class);
        $checkIns->method('findInRange')->willReturn($rows);

        $service = new CrisisDetectionService(
            $checkIns,
            $this->createMock(GameSettingRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(LoggerInterface::class),
        );

        self::assertSame(5, $service->getWearyStreakDays($user));
    }

    public function testWearyStreakBreaksOnNonCrisisDay(): void
    {
        $user = $this->makeUser(Uuid::v4());

        $rows = [
            // 4 days ago was STEADY — streak should never reach that far.
            $this->makeRow(PsychStatus::STEADY, 4),
            // But today and the prior 3 days are WEARY → streak = 4.
            $this->makeRow(PsychStatus::WEARY, 3),
            $this->makeRow(PsychStatus::WEARY, 2),
            $this->makeRow(PsychStatus::WEARY, 1),
            $this->makeRow(PsychStatus::WEARY, 0),
        ];

        $checkIns = $this->createMock(PsychCheckInRepository::class);
        $checkIns->method('findInRange')->willReturn($rows);

        $service = new CrisisDetectionService(
            $checkIns,
            $this->createMock(GameSettingRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(LoggerInterface::class),
        );

        self::assertSame(4, $service->getWearyStreakDays($user));
    }

    public function testWearyStreakBreaksOnMissingDay(): void
    {
        $user = $this->makeUser(Uuid::v4());

        // Gap at day 2 — streak only counts today + day 1.
        $rows = [
            $this->makeRow(PsychStatus::WEARY, 3),
            $this->makeRow(PsychStatus::WEARY, 1),
            $this->makeRow(PsychStatus::WEARY, 0),
        ];

        $checkIns = $this->createMock(PsychCheckInRepository::class);
        $checkIns->method('findInRange')->willReturn($rows);

        $service = new CrisisDetectionService(
            $checkIns,
            $this->createMock(GameSettingRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(LoggerInterface::class),
        );

        self::assertSame(2, $service->getWearyStreakDays($user));
    }
}
