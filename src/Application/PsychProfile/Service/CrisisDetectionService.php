<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\Service;

use App\Domain\Config\Entity\GameSetting;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\User\Entity\User;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\PsychProfile\Repository\PsychCheckInRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Detects a rolling "5 of last 7 days" WEARY/SCATTERED pattern.
 *
 * Application layer (PsychProfile bounded context). Read-only from the
 * check-in perspective; writes a single `game_settings` row to enforce a
 * per-user cooldown so the same crisis is not logged repeatedly.
 *
 * Spec §1 decision 1 — "log only" in beta. No user-facing nudge, no
 * admin webhook. An admin spots it through the `psych` Monolog channel.
 *
 * Cooldown contract:
 *  - Key: `psych.crisis_last_flagged_{userIdHex}` (ISO-8601 datetime).
 *  - When present and within `psych.crisis_cooldown_days` (default 30) →
 *    `hasCrisisPattern()` still returns the boolean, but the warning log
 *    is suppressed so we don't spam the audit channel.
 */
final class CrisisDetectionService
{
    /** Number of qualifying days in the 7-day window (spec §1 decision 1). */
    public const DEFAULT_THRESHOLD = 5;

    /** Cooldown in days between repeated crisis logs for the same user. */
    public const DEFAULT_COOLDOWN_DAYS = 30;

    public const SETTING_THRESHOLD = 'psych.crisis_threshold_days';
    public const SETTING_COOLDOWN = 'psych.crisis_cooldown_days';
    public const SETTING_CATEGORY = 'psych';

    /** PsychStatus values that count as a "crisis" day. */
    private const CRISIS_STATUSES = [
        PsychStatus::WEARY->value,
        PsychStatus::SCATTERED->value,
    ];

    public function __construct(
        private readonly PsychCheckInRepository $checkInRepository,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Returns true when the user has `$threshold` of the last 7 calendar
     * days marked WEARY or SCATTERED.
     *
     * Side effect: writes a `psych.crisis-pattern` warning to the logger
     * the first time a user enters the pattern; subsequent calls within
     * the cooldown window return the same boolean without re-logging.
     */
    public function hasCrisisPattern(User $user): bool
    {
        $threshold = $this->loadThreshold();
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $start = $today->modify('-6 day');

        $rows = $this->checkInRepository->findInRange($user, $start, $today);

        $crisisDays = 0;
        $uniqueDates = [];
        foreach ($rows as $row) {
            // Dedupe by date — one row per day only (defence in depth).
            $dateKey = $row->getCheckedInOn()->format('Y-m-d');
            if (isset($uniqueDates[$dateKey])) {
                continue;
            }
            $uniqueDates[$dateKey] = true;

            if (in_array($row->getAssignedStatus()->value, self::CRISIS_STATUSES, true)) {
                ++$crisisDays;
            }
        }

        $isCrisis = $crisisDays >= $threshold;
        if (!$isCrisis) {
            return false;
        }

        // Flagged — check cooldown before writing to the audit log.
        if ($this->isWithinCooldown($user)) {
            return true;
        }

        $this->logger->warning('psych.crisis-pattern', [
            'userId' => $user->getId()->toRfc4122(),
            'window' => 7,
            'threshold' => $threshold,
            'crisisDays' => $crisisDays,
        ]);
        $this->markFlagged($user);

        return true;
    }

    private function loadThreshold(): int
    {
        $setting = $this->gameSettingRepository->findByKey(self::SETTING_THRESHOLD);
        if ($setting === null) {
            return self::DEFAULT_THRESHOLD;
        }

        $value = (int) $setting->getValue();

        return $value > 0 ? $value : self::DEFAULT_THRESHOLD;
    }

    private function loadCooldownDays(): int
    {
        $setting = $this->gameSettingRepository->findByKey(self::SETTING_COOLDOWN);
        if ($setting === null) {
            return self::DEFAULT_COOLDOWN_DAYS;
        }

        $value = (int) $setting->getValue();

        return $value > 0 ? $value : self::DEFAULT_COOLDOWN_DAYS;
    }

    private function cooldownKey(User $user): string
    {
        return sprintf('psych.crisis_last_flagged_%s', $user->getId()->toRfc4122());
    }

    private function isWithinCooldown(User $user): bool
    {
        $row = $this->gameSettingRepository->findByKey($this->cooldownKey($user));
        if ($row === null) {
            return false;
        }

        $raw = trim($row->getValue());
        if ($raw === '') {
            return false;
        }

        try {
            $lastFlaggedAt = new \DateTimeImmutable($raw);
        } catch (\Exception) {
            return false;
        }

        $cooldownDays = $this->loadCooldownDays();
        $cutoff = (new \DateTimeImmutable())->modify(sprintf('-%d day', $cooldownDays));

        return $lastFlaggedAt > $cutoff;
    }

    private function markFlagged(User $user): void
    {
        $key = $this->cooldownKey($user);
        $nowIso = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        $existing = $this->gameSettingRepository->findByKey($key);
        if ($existing === null) {
            $setting = new GameSetting();
            $setting->setCategory(self::SETTING_CATEGORY);
            $setting->setKey($key);
            $setting->setValue($nowIso);
            $setting->setDescription('Per-user crisis-pattern cooldown marker (auto-managed).');
            $this->entityManager->persist($setting);
        } else {
            $existing->setValue($nowIso);
        }

        $this->entityManager->flush();
    }

    /**
     * Psych v2 (spec §1.5): consecutive days — ending with today — where
     * the user's assigned status was WEARY or SCATTERED. A gap (any other
     * status or a missing day) breaks the streak.
     *
     * Walks from today backwards up to 30 days. Skipped rows inherit the
     * prior status at CheckInService, so a skip-chain of WEARY days still
     * counts for the streak — this matches the clinical intent of "you
     * keep feeling heavy for N days".
     */
    public function getWearyStreakDays(User $user): int
    {
        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $start = $today->modify('-29 day');

        $rows = $this->checkInRepository->findInRange($user, $start, $today);

        // Index by date so the walk below is O(days) not O(rows).
        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row->getCheckedInOn()->format('Y-m-d')] = $row;
        }

        $streak = 0;
        for ($i = 0; $i < 30; ++$i) {
            $day = $today->modify(sprintf('-%d day', $i));
            $key = $day->format('Y-m-d');
            if (!isset($byDate[$key])) {
                break;
            }

            $status = $byDate[$key]->getAssignedStatus();
            if ($status !== PsychStatus::WEARY && $status !== PsychStatus::SCATTERED) {
                break;
            }

            ++$streak;
        }

        return $streak;
    }
}
