<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\Service;

use App\Domain\Config\Entity\GameSetting;
use App\Domain\User\Entity\User;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Applies the +10% completion XP bonus when a user submits a full psych
 * check-in (spec §1.1 + §2.2).
 *
 * Application layer (PsychProfile bounded context). State is carried in
 * two kinds of `game_settings` rows under the `psych` category:
 *
 *   - `psych.bonus_marker_{userIdHex}_{Ymd}` — presence means the daily
 *     bonus is AVAILABLE (check-in was full). Value is the ISO-8601
 *     timestamp of the check-in that set it.
 *   - `psych.bonus_applied_{userIdHex}_{Ymd}` — presence means the bonus
 *     has ALREADY been applied for that day; idempotency guard.
 *
 * Weekly cap (5/7, spec §1.1): `applyIfEligible` counts existing
 * `psych.bonus_applied_*` rows for the user within the last 7 days.
 * The *current* day does not count until the bonus fires — so exactly 5
 * distinct days in the rolling 7-day window can earn the bonus.
 *
 * NOT `final` — tests in other services mock this dependency.
 */
class CompletionBonusService
{
    public const SETTING_CATEGORY = 'psych';
    public const SETTING_BONUS_PCT = 'psych.completion_bonus_pct';
    public const SETTING_WEEKLY_CAP = 'psych.completion_bonus_weekly_cap';

    /** Fallback bonus when the setting is missing (spec §1.1). */
    public const DEFAULT_BONUS_PCT = 10;

    /** Fallback weekly cap when the setting is missing (spec §1.1). */
    public const DEFAULT_WEEKLY_CAP = 5;

    private const MARKER_PREFIX = 'psych.bonus_marker_';
    private const APPLIED_PREFIX = 'psych.bonus_applied_';

    public function __construct(
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Mark today's check-in as eligible for the completion bonus on the
     * next health-sync XP event. Idempotent per (user, local date).
     */
    public function markEligibility(User $user, \DateTimeImmutable $date): void
    {
        $key = self::markerKey($user, $date);
        $existing = $this->gameSettingRepository->findByKey($key);
        if ($existing !== null) {
            return;
        }

        $setting = new GameSetting();
        $setting->setCategory(self::SETTING_CATEGORY);
        $setting->setKey($key);
        $setting->setValue((new \DateTimeImmutable())->format(\DateTimeInterface::ATOM));
        $setting->setDescription('Per-user completion-bonus eligibility marker (auto-managed).');

        $this->entityManager->persist($setting);
        $this->entityManager->flush();
    }

    /**
     * Apply the completion bonus if the user has an eligibility marker
     * for today AND has not already consumed it AND is under the weekly
     * cap. Returns the post-bonus XP amount (may equal the input).
     *
     * Writes the "applied" idempotency marker on success.
     */
    public function applyIfEligible(User $user, \DateTimeImmutable $date, int $baseXp): int
    {
        if (!$this->isEligible($user, $date)) {
            return $baseXp;
        }

        $bonusPct = $this->loadBonusPct();
        $multiplier = 1.0 + ($bonusPct / 100.0);
        $withBonus = (int) round($baseXp * $multiplier);

        $this->markApplied($user, $date);

        $this->logger->info('psych.completion-bonus', [
            'userId' => $user->getId()->toRfc4122(),
            'date' => $date->format('Y-m-d'),
            'baseXp' => $baseXp,
            'bonusXp' => $withBonus,
            'bonusPct' => $bonusPct,
        ]);

        return $withBonus;
    }

    /**
     * Inspect-only eligibility check (no side effects). Used by tests
     * and the XP multiplier log line in XpAwardService.
     */
    public function isEligible(User $user, \DateTimeImmutable $date): bool
    {
        if ($this->gameSettingRepository->findByKey(self::markerKey($user, $date)) === null) {
            return false;
        }
        if ($this->gameSettingRepository->findByKey(self::appliedKey($user, $date)) !== null) {
            return false;
        }

        return $this->appliedInLast7Days($user, $date) < $this->loadWeeklyCap();
    }

    /** Bonus percentage (10 by default, tunable via settings). */
    public function getBonusPct(): int
    {
        return $this->loadBonusPct();
    }

    /**
     * Count applied-bonus markers in the 6 prior days. Combined with the
     * pending "today" firing the bonus, that totals at most 7 days in the
     * rolling window — matching the spec's "5 of 7" cap wording (§1.1).
     */
    private function appliedInLast7Days(User $user, \DateTimeImmutable $today): int
    {
        $count = 0;
        for ($i = 1; $i <= 6; ++$i) {
            $day = $today->modify(sprintf('-%d day', $i));
            if ($this->gameSettingRepository->findByKey(self::appliedKey($user, $day)) !== null) {
                ++$count;
            }
        }

        return $count;
    }

    private function markApplied(User $user, \DateTimeImmutable $date): void
    {
        $key = self::appliedKey($user, $date);
        $existing = $this->gameSettingRepository->findByKey($key);
        if ($existing !== null) {
            return;
        }

        $setting = new GameSetting();
        $setting->setCategory(self::SETTING_CATEGORY);
        $setting->setKey($key);
        $setting->setValue((new \DateTimeImmutable())->format(\DateTimeInterface::ATOM));
        $setting->setDescription('Per-user completion-bonus applied marker (auto-managed).');

        $this->entityManager->persist($setting);
        $this->entityManager->flush();
    }

    private function loadBonusPct(): int
    {
        $setting = $this->gameSettingRepository->findByKey(self::SETTING_BONUS_PCT);
        if ($setting === null) {
            return self::DEFAULT_BONUS_PCT;
        }

        $value = (int) $setting->getValue();

        return $value > 0 ? $value : self::DEFAULT_BONUS_PCT;
    }

    private function loadWeeklyCap(): int
    {
        $setting = $this->gameSettingRepository->findByKey(self::SETTING_WEEKLY_CAP);
        if ($setting === null) {
            return self::DEFAULT_WEEKLY_CAP;
        }

        $value = (int) $setting->getValue();

        return $value > 0 ? $value : self::DEFAULT_WEEKLY_CAP;
    }

    public static function markerKey(User $user, \DateTimeImmutable $date): string
    {
        return self::MARKER_PREFIX . $user->getId()->toRfc4122() . '_' . $date->format('Ymd');
    }

    public static function appliedKey(User $user, \DateTimeImmutable $date): string
    {
        return self::APPLIED_PREFIX . $user->getId()->toRfc4122() . '_' . $date->format('Ymd');
    }
}
