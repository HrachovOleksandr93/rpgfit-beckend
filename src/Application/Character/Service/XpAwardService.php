<?php

declare(strict_types=1);

namespace App\Application\Character\Service;

use App\Domain\Character\Entity\CharacterStats;
use App\Domain\Character\Entity\ExperienceLog;
use App\Domain\Health\Enum\HealthDataType;
use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;

/**
 * Orchestrates the full XP award flow: calculate, log, update stats, check level-up.
 *
 * Application layer (Character bounded context). Called after a health data sync
 * to translate the synced health metrics into XP, persist the award in ExperienceLog,
 * update the cached totalXp/level on CharacterStats, and report whether a level-up occurred.
 */
final class XpAwardService
{
    public function __construct(
        private readonly XpCalculationService $xpCalculationService,
        private readonly LevelingService $levelingService,
        private readonly ExperienceLogRepository $experienceLogRepository,
        private readonly CharacterStatsRepository $characterStatsRepository,
    ) {
    }

    /**
     * Award XP from a health data sync batch.
     *
     * @param User  $user             The user who synced health data
     * @param array<array{type: HealthDataType, value: float}> $healthDataPoints
     *
     * @return array{xpAwarded: int, newTotalXp: int, level: int, leveledUp: bool, levelProgress: array}
     */
    public function awardXpFromHealthSync(User $user, array $healthDataPoints): array
    {
        // Calculate XP (with daily cap applied)
        $xpAwarded = $this->xpCalculationService->calculateDailyXp($healthDataPoints);

        if ($xpAwarded <= 0) {
            $stats = $this->getOrCreateStats($user);

            return [
                'xpAwarded' => 0,
                'newTotalXp' => $stats->getTotalXp(),
                'level' => $stats->getLevel(),
                'leveledUp' => false,
                'levelProgress' => $this->levelingService->getLevelProgress($stats->getTotalXp()),
            ];
        }

        // Log the XP award
        $log = new ExperienceLog();
        $log->setUser($user);
        $log->setAmount($xpAwarded);
        $log->setSource('health_sync');
        $log->setDescription('XP from health data sync');
        $this->experienceLogRepository->save($log);

        // Update character stats
        $stats = $this->getOrCreateStats($user);
        $oldLevel = $stats->getLevel();

        $newTotalXp = $stats->getTotalXp() + $xpAwarded;
        $stats->setTotalXp($newTotalXp);

        $newLevel = $this->levelingService->getLevelForTotalXp($newTotalXp);
        $stats->setLevel($newLevel);

        $this->characterStatsRepository->save($stats);

        return [
            'xpAwarded' => $xpAwarded,
            'newTotalXp' => $newTotalXp,
            'level' => $newLevel,
            'leveledUp' => $newLevel > $oldLevel,
            'levelProgress' => $this->levelingService->getLevelProgress($newTotalXp),
        ];
    }

    /** Get existing CharacterStats or create a fresh one for the user. */
    private function getOrCreateStats(User $user): CharacterStats
    {
        $stats = $this->characterStatsRepository->findByUser($user);

        if ($stats === null) {
            $stats = new CharacterStats();
            $stats->setUser($user);
            $this->characterStatsRepository->save($stats);
        }

        return $stats;
    }
}
