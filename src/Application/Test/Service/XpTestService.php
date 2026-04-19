<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Application\Character\Service\LevelingService;
use App\Domain\Character\Entity\CharacterStats;
use App\Domain\Character\Entity\ExperienceLog;
use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use App\Infrastructure\Config\Repository\GameSettingRepository;

/**
 * Test-harness XP / level mutator.
 *
 * Delegates level math to `LevelingService`; the `force` flag only
 * relaxes the `xp_daily_cap` business rule, never the DB schema. Writes
 * an ExperienceLog row with `source = test_harness_*` so analytics can
 * filter synthetic XP out of real dashboards.
 */
final class XpTestService
{
    public function __construct(
        private readonly LevelingService $levelingService,
        private readonly ExperienceLogRepository $experienceLogRepository,
        private readonly CharacterStatsRepository $characterStatsRepository,
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
    }

    /**
     * Grant raw XP to the user. When `$force` is true the daily cap is
     * bypassed; otherwise the effective amount is clamped to whatever
     * headroom the user has left today.
     *
     * @return array{xpAdded: int, totalXp: int, level: int, leveledUp: bool}
     */
    public function grantXp(User $user, int $amount, bool $force, string $source = 'test_harness_grant'): array
    {
        // Domain invariants: clamp to the range documented in the spec.
        $amount = max(-1_000_000, min(1_000_000, $amount));

        if (!$force && $amount > 0) {
            $amount = $this->applyDailyCap($user, $amount);
        }

        $stats = $this->getOrCreateStats($user);
        $oldLevel = $stats->getLevel();

        $newTotalXp = max(0, $stats->getTotalXp() + $amount);
        $stats->setTotalXp($newTotalXp);

        $newLevel = $this->levelingService->getLevelForTotalXp($newTotalXp);
        $stats->setLevel($newLevel);
        $this->characterStatsRepository->save($stats);

        if ($amount !== 0) {
            $log = new ExperienceLog();
            $log->setUser($user);
            $log->setAmount($amount);
            $log->setSource($source);
            $log->setDescription(sprintf('Test harness XP grant (force=%s)', $force ? '1' : '0'));
            $this->experienceLogRepository->save($log);
        }

        return [
            'xpAdded' => $amount,
            'totalXp' => $newTotalXp,
            'level' => $newLevel,
            'leveledUp' => $newLevel > $oldLevel,
        ];
    }

    /**
     * Jump the user directly to a specific level. Respects `1..level_max`;
     * recomputes `totalXp` to the exact cumulative threshold.
     *
     * @return array{oldLevel: int, newLevel: int, totalXp: int}
     */
    public function setLevel(User $user, int $level): array
    {
        $maxLevel = (int) ($this->gameSettingRepository->getAllAsMap()['level_max'] ?? '100');
        $level = max(1, min($maxLevel, $level));

        $stats = $this->getOrCreateStats($user);
        $oldLevel = $stats->getLevel();
        $oldTotalXp = $stats->getTotalXp();

        // Cumulative XP for "level N" = sum of XP needed to clear levels 1..N-1.
        $targetTotalXp = $this->levelingService->getTotalXpForLevel(max(0, $level - 1));

        $stats->setLevel($level);
        $stats->setTotalXp($targetTotalXp);
        $this->characterStatsRepository->save($stats);

        $delta = $targetTotalXp - $oldTotalXp;
        if ($delta !== 0) {
            $log = new ExperienceLog();
            $log->setUser($user);
            $log->setAmount($delta);
            $log->setSource('test_harness_level_set');
            $log->setDescription(sprintf('Test harness level set -> %d', $level));
            $this->experienceLogRepository->save($log);
        }

        return [
            'oldLevel' => $oldLevel,
            'newLevel' => $level,
            'totalXp' => $targetTotalXp,
        ];
    }

    /**
     * Advance the user by `$steps` levels, respecting the curve unless
     * `$force` is passed (force still caps at level_max).
     *
     * @return array{oldLevel: int, newLevel: int, totalXp: int}
     */
    public function grantLevels(User $user, int $steps, bool $force): array
    {
        $maxLevel = (int) ($this->gameSettingRepository->getAllAsMap()['level_max'] ?? '100');
        $steps = max(1, $steps);

        $stats = $this->getOrCreateStats($user);
        $target = min($maxLevel, $stats->getLevel() + $steps);

        if (!$force && $target > $stats->getLevel() + 5) {
            // Gentle guard so non-force calls cannot fast-track past 5 levels.
            $target = $stats->getLevel() + 5;
        }

        return $this->setLevel($user, $target);
    }

    private function getOrCreateStats(User $user): CharacterStats
    {
        $stats = $this->characterStatsRepository->findByUser($user);
        if ($stats !== null) {
            return $stats;
        }

        $stats = new CharacterStats();
        $stats->setUser($user);
        $this->characterStatsRepository->save($stats);

        return $stats;
    }

    /**
     * Reduce `$amount` to whatever fits under today's `xp_daily_cap`.
     */
    private function applyDailyCap(User $user, int $amount): int
    {
        $cap = (int) ($this->gameSettingRepository->getAllAsMap()['xp_daily_cap'] ?? '3000');
        if ($cap <= 0) {
            return $amount;
        }

        $today = new \DateTimeImmutable('today');
        $qb = $this->experienceLogRepository->createQueryBuilder('e')
            ->select('COALESCE(SUM(e.amount), 0)')
            ->where('e.user = :user')
            ->andWhere('e.earnedAt >= :today')
            ->andWhere('e.amount > 0')
            ->setParameter('user', $user)
            ->setParameter('today', $today);

        $alreadyEarned = (int) $qb->getQuery()->getSingleScalarResult();
        $headroom = max(0, $cap - $alreadyEarned);

        return min($amount, $headroom);
    }
}
