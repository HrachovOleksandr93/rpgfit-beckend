<?php

declare(strict_types=1);

namespace App\Application\Character\Service;

use App\Application\PsychProfile\Service\CompletionBonusService;
use App\Application\PsychProfile\Service\PsychStatusModifierService;
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
 *
 * Psych v2 (spec 2026-04-19): after the status multiplier runs, the
 * CompletionBonusService applies a +10% multiplicative bonus when the
 * user has a completion-bonus eligibility marker for today. The bonus
 * stacks on top of the status multiplier (e.g. CHARGED x1.15 x 1.10 =
 * 1.265 effective). Weekly cap of 5/7 days is enforced inside the bonus
 * service itself.
 */
final class XpAwardService
{
    public function __construct(
        private readonly XpCalculationService $xpCalculationService,
        private readonly LevelingService $levelingService,
        private readonly ExperienceLogRepository $experienceLogRepository,
        private readonly CharacterStatsRepository $characterStatsRepository,
        private readonly PsychStatusModifierService $psychModifier,
        private readonly ?CompletionBonusService $completionBonusService = null,
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

        // Apply psych status multiplier (1.00 when feature off / not opted in).
        // Health-sync XP counts as "workout" context per §4 of the psych spec —
        // sleep/steps/activity already normalised by XpCalculationService.
        $multiplier = $this->psychModifier->getXpMultiplier($user, PsychStatusModifierService::CONTEXT_WORKOUT);
        if ($multiplier !== 1.0) {
            $xpAwarded = (int) round($xpAwarded * $multiplier);
        }

        // Psych v2: apply completion XP bonus if the user answered a full
        // daily check-in today. Stacks multiplicatively AFTER status.
        $xpBeforeBonus = $xpAwarded;
        if ($this->completionBonusService !== null) {
            $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
            $xpAwarded = $this->completionBonusService->applyIfEligible($user, $today, $xpAwarded);
        }
        $bonusFired = $xpAwarded !== $xpBeforeBonus;

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
        if ($bonusFired) {
            $bonusPct = $this->completionBonusService?->getBonusPct() ?? 0;
            $bonusMul = 1.0 + ($bonusPct / 100.0);
            $log->setDescription(sprintf(
                'XP from health data sync (psych ×%.2f ×%.2f bonus)',
                $multiplier,
                $bonusMul,
            ));
        } else {
            $log->setDescription(sprintf(
                'XP from health data sync (psych ×%.2f)',
                $multiplier,
            ));
        }
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
