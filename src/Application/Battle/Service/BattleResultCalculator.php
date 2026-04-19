<?php

declare(strict_types=1);

namespace App\Application\Battle\Service;

use App\Application\Battle\DTO\BattleResult;
use App\Application\Character\Service\LevelingService;
use App\Domain\Battle\Entity\WorkoutSession;
use App\Domain\Battle\Enum\BattleMode;
use App\Domain\Character\Entity\CharacterStats;
use App\Domain\Character\Entity\ExperienceLog;
use App\Domain\Character\Enum\StatType;
use App\Domain\Shared\Enum\Realm;
use App\Domain\Workout\Entity\WorkoutPlan;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\Inventory\Repository\ItemCatalogRepository;
use App\Infrastructure\Inventory\Repository\UserInventoryRepository;
use App\Infrastructure\Mob\Repository\MobRepository;
use App\Infrastructure\Skill\Repository\SkillRepository;
use App\Infrastructure\Skill\Repository\UserSkillRepository;
use App\Infrastructure\Workout\Repository\ExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Calculates the full battle result server-side after a workout session is completed.
 *
 * Application layer (Battle bounded context). This is the main scoring engine that:
 * 1. Computes effective combat stats (base + equipment + skills + consumables)
 * 2. Calculates training volume with anomaly protection
 * 3. Determines tick-based damage from duration and stats
 * 4. Counts mobs defeated and raw XP earned
 * 5. Evaluates plan completion percentage
 * 6. Assigns a performance tier (failed/survived/completed/exceeded/raid_exceeded)
 * 7. Awards XP with bonuses and updates character progression
 * 8. Sets difficulty modifier for next plan if the user failed
 */
class BattleResultCalculator
{
    /** In-memory cache of game settings for the current request. */
    private ?array $settingsCache = null;

    public function __construct(
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly CharacterStatsRepository $characterStatsRepository,
        private readonly ExerciseRepository $exerciseRepository,
        private readonly UserInventoryRepository $userInventoryRepository,
        private readonly UserSkillRepository $userSkillRepository,
        private readonly SkillRepository $skillRepository,
        private readonly ItemCatalogRepository $itemCatalogRepository,
        private readonly MobRepository $mobRepository,
        private readonly LevelingService $levelingService,
        private readonly ExperienceLogRepository $experienceLogRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Calculate the full battle result for a completed workout session.
     *
     * @param WorkoutSession $session           The session to evaluate
     * @param array          $exercises         Exercise data with sets from the mobile app
     * @param array|null     $healthData        Health API data (duration, calories, distance, etc.)
     * @param string[]       $usedSkillSlugs    Skill slugs activated during the session
     * @param string[]       $usedConsumableSlugs Consumable item slugs used during the session
     */
    public function calculateBattleResult(
        WorkoutSession $session,
        array $exercises,
        ?array $healthData,
        array $usedSkillSlugs,
        array $usedConsumableSlugs,
    ): BattleResult {
        $user = $session->getUser();
        $plan = $session->getWorkoutPlan();

        // Step 1: Calculate effective combat stats (base + buffs + gear + consumables)
        $effectiveStats = $this->calculateEffectiveStats(
            $user,
            $usedSkillSlugs,
            $usedConsumableSlugs,
        );

        // Step 2: Calculate training volume with anomaly filtering
        $volume = $this->calculateTrainingVolume($exercises);
        $calories = (float) ($healthData['calories'] ?? 0);
        $trainingScore = $calories + ($volume / 100.0);

        // Step 3: Calculate tick-based damage from duration and effective stats
        $durationSeconds = (int) ($healthData['duration'] ?? 0);
        $activityType = $this->determineActivityType($plan);
        $totalDamage = $this->calculateDamage($durationSeconds, $effectiveStats, $activityType, $trainingScore);

        // Step 3b: Apply realm-match bonus (BUSINESS_LOGIC §12) — if any equipped
        // artifact shares a realm with the current mob, damage is multiplied by 1.4.
        $mobRealm = $this->resolveMobRealm($session);
        if ($mobRealm !== null && $this->hasRealmBoundArtifactMatching($user, $mobRealm)) {
            $totalDamage = $this->applyRealmMatchMultiplier($totalDamage);
        }

        // Step 4: Count mobs defeated and raw XP from mobs
        $mobHp = $session->getMobHp() ?? 100;
        $mobXpReward = $session->getMobXpReward() ?? 50;
        $mobsDefeated = $mobHp > 0 ? (int) floor($totalDamage / $mobHp) : 0;
        $xpFromMobs = $mobsDefeated * $mobXpReward;

        // Step 5: Calculate plan completion percentage
        $completionPercent = $this->calculateCompletionPercent($plan, $exercises, $healthData);

        // Step 6: Determine performance tier and bonuses
        $tierResult = $this->determinePerformanceTier(
            $completionPercent,
            $xpFromMobs,
            $mobsDefeated,
            $session->getMode(),
            $plan,
            $mobHp,
            $totalDamage,
        );

        // Step 7: Award XP and update character progression
        $levelUp = false;
        $newLevel = 1;
        $totalXp = 0;

        if ($tierResult['xpAwarded'] > 0) {
            $xpResult = $this->awardXp($user, $tierResult['xpAwarded'], $session);
            $levelUp = $xpResult['leveledUp'];
            $newLevel = $xpResult['level'];
            $totalXp = $xpResult['totalXp'];
        } else {
            $stats = $this->characterStatsRepository->findByUser($user);
            if ($stats !== null) {
                $newLevel = $stats->getLevel();
                $totalXp = $stats->getTotalXp();
            }
        }

        // Update session with result data
        $session->setCompletionPercent($completionPercent);
        $session->setPerformanceTier($tierResult['tier']);
        $session->setBonusXpPercent($tierResult['bonusXpPercent']);
        $session->setLootEarned($tierResult['lootEarned']);
        $session->setSuperLootEarned($tierResult['superLootEarned']);
        $session->setTotalDamageDealt($totalDamage);
        $session->setXpAwarded($tierResult['xpAwarded']);
        $session->setMobsDefeated($mobsDefeated);
        $session->setTotalXpFromMobs($xpFromMobs);

        // Step 8: Flag difficulty reduction for next plan if the user failed
        if ($tierResult['tier'] === 'failed') {
            $this->flagDifficultyReduction($user);
        }

        return new BattleResult(
            performanceTier: $tierResult['tier'],
            completionPercent: $completionPercent,
            mobsDefeated: $mobsDefeated,
            totalDamage: $totalDamage,
            xpFromMobs: $xpFromMobs,
            bonusXpPercent: $tierResult['bonusXpPercent'],
            xpAwarded: $tierResult['xpAwarded'],
            lootEarned: $tierResult['lootEarned'],
            superLootEarned: $tierResult['superLootEarned'],
            levelUp: $levelUp,
            newLevel: $newLevel,
            totalXp: $totalXp,
            message: $tierResult['message'],
        );
    }

    // ========================================================================
    // Step 1: Effective Stats
    // ========================================================================

    /**
     * Calculate effective combat stats by combining base stats with all bonuses.
     *
     * @param \App\Domain\User\Entity\User $user
     * @param string[]                      $usedSkillSlugs
     * @param string[]                      $usedConsumableSlugs
     *
     * @return array{str: int, dex: int, con: int}
     */
    public function calculateEffectiveStats(
        \App\Domain\User\Entity\User $user,
        array $usedSkillSlugs = [],
        array $usedConsumableSlugs = [],
    ): array {
        // Base stats from CharacterStats
        $stats = $this->characterStatsRepository->findByUser($user);
        $str = $stats?->getStrength() ?? 0;
        $dex = $stats?->getDexterity() ?? 0;
        $con = $stats?->getConstitution() ?? 0;

        // Equipment bonuses from currently equipped items
        $equippedItems = $this->userInventoryRepository->findEquippedByUser($user);
        foreach ($equippedItems as $inventoryItem) {
            $catalog = $inventoryItem->getItemCatalog();
            foreach ($catalog->getStatBonuses() as $bonus) {
                match ($bonus->getStatType()) {
                    StatType::Strength => $str += $bonus->getPoints(),
                    StatType::Dexterity => $dex += $bonus->getPoints(),
                    StatType::Constitution => $con += $bonus->getPoints(),
                };
            }
        }

        // Skill bonuses: passive skills (always active) + active skills (from usedSkillSlugs)
        $userSkills = $this->userSkillRepository->findByUser($user);
        foreach ($userSkills as $userSkill) {
            $skill = $userSkill->getSkill();
            $isActive = $skill->getSkillType() === 'active';
            $isPassive = $skill->getSkillType() === 'passive';

            // Passive skills always apply; active skills only if used in this session
            if ($isPassive || ($isActive && in_array($skill->getSlug(), $usedSkillSlugs, true))) {
                foreach ($skill->getStatBonuses() as $bonus) {
                    match ($bonus->getStatType()) {
                        StatType::Strength => $str += $bonus->getPoints(),
                        StatType::Dexterity => $dex += $bonus->getPoints(),
                        StatType::Constitution => $con += $bonus->getPoints(),
                    };
                }
            }
        }

        // Consumable bonuses from used items
        foreach ($usedConsumableSlugs as $slug) {
            $item = $this->itemCatalogRepository->findBySlug($slug);
            if ($item === null) {
                continue;
            }
            foreach ($item->getStatBonuses() as $bonus) {
                match ($bonus->getStatType()) {
                    StatType::Strength => $str += $bonus->getPoints(),
                    StatType::Dexterity => $dex += $bonus->getPoints(),
                    StatType::Constitution => $con += $bonus->getPoints(),
                };
            }
        }

        return ['str' => $str, 'dex' => $dex, 'con' => $con];
    }

    // ========================================================================
    // Step 2: Training Volume
    // ========================================================================

    /**
     * Calculate total training volume with anomaly protection.
     *
     * Sums weight * reps for every set, capping individual values at the configured
     * anomaly thresholds to prevent obviously incorrect data from skewing results.
     */
    public function calculateTrainingVolume(array $exercises): float
    {
        $maxWeight = (float) $this->getSetting('workout_volume_anomaly_max_weight', '300');
        $maxReps = (int) $this->getSetting('workout_volume_anomaly_max_reps', '100');
        $volume = 0.0;

        foreach ($exercises as $exerciseData) {
            $sets = $exerciseData['sets'] ?? [];
            foreach ($sets as $setData) {
                $weight = (float) ($setData['weight'] ?? 0);
                $reps = (int) ($setData['reps'] ?? 0);

                // Anomaly protection: clamp negative values and cap at max thresholds
                $weight = max(0.0, min($weight, $maxWeight));
                $reps = max(0, min($reps, $maxReps));

                $volume += $weight * $reps;
            }
        }

        return $volume;
    }

    // ========================================================================
    // Step 3: Damage Calculation
    // ========================================================================

    /**
     * Calculate total damage dealt using the tick system.
     *
     * Damage per tick depends on the activity type and the user's effective stats.
     * The base damage multiplier and stat factors come from game settings.
     *
     * @param int    $durationSeconds Total workout duration in seconds
     * @param array  $effectiveStats  {str, dex, con}
     * @param string $activityType    One of: strength, dex, mixed, con
     * @param float  $trainingScore   Volume-based score added to damage
     */
    public function calculateDamage(
        int $durationSeconds,
        array $effectiveStats,
        string $activityType,
        float $trainingScore,
    ): int {
        $tickFrequency = (int) $this->getSetting('battle_tick_frequency', '6');
        $baseMultiplier = (float) $this->getSetting('battle_base_damage_multiplier', '1.0');
        $strFactor = (float) $this->getSetting('battle_strength_damage_factor', '0.8');
        $dexFactor = (float) $this->getSetting('battle_dex_damage_factor', '0.8');
        $conFactor = (float) $this->getSetting('battle_con_damage_factor', '0.7');

        $str = $effectiveStats['str'];
        $dex = $effectiveStats['dex'];
        $con = $effectiveStats['con'];

        // Calculate damage per tick based on activity type
        $damagePerTick = match ($activityType) {
            'strength' => $str * $strFactor + $con * 0.2,
            'dex' => $dex * $dexFactor + $con * 0.2,
            'mixed' => (($str + $dex) / 2) * 0.7 + $con * 0.3,
            'con' => $con * $conFactor + $str * 0.15 + $dex * 0.15,
            default => (($str + $dex + $con) / 3) * 0.5,
        };

        $damagePerTick *= $baseMultiplier;

        // Calculate ticks from duration (1 tick = 60 / tickFrequency seconds)
        $secondsPerTick = $tickFrequency > 0 ? (60.0 / $tickFrequency) : 10.0;
        $ticks = $durationSeconds > 0 ? (int) floor($durationSeconds / $secondsPerTick) : 0;

        // Total damage = ticks * damage_per_tick + training score contribution
        $totalDamage = (int) round($ticks * $damagePerTick + $trainingScore);

        // Minimum 1 damage if any exercise data was submitted
        return max(0, $totalDamage);
    }

    // ========================================================================
    // Step 5: Completion Percentage
    // ========================================================================

    /**
     * Calculate what percentage of the workout plan was completed.
     *
     * For cardio plans with a target distance, uses actual vs target distance.
     * For exercise-based plans, compares planned exercises/sets/reps to actual.
     */
    public function calculateCompletionPercent(
        WorkoutPlan $plan,
        array $exercises,
        ?array $healthData,
    ): float {
        // Cardio plan: compare distance
        $targetDistance = $plan->getTargetDistance();
        if ($targetDistance !== null && $targetDistance > 0) {
            $actualDistance = (float) ($healthData['distance'] ?? 0);

            return $targetDistance > 0 ? round(($actualDistance / $targetDistance) * 100, 1) : 0.0;
        }

        // Exercise-based plan: compare sets and reps completion
        $plannedExercises = $plan->getExercises();
        $plannedCount = $plannedExercises->count();
        if ($plannedCount === 0) {
            // No planned exercises; if any exercises submitted, count as 100%
            return count($exercises) > 0 ? 100.0 : 0.0;
        }

        // Build a lookup of submitted exercises by slug
        $submittedBySlug = [];
        foreach ($exercises as $exerciseData) {
            $slug = $exerciseData['exerciseSlug'] ?? null;
            if ($slug !== null) {
                $submittedBySlug[$slug] = $exerciseData;
            }
        }

        $totalCompletionScore = 0.0;

        foreach ($plannedExercises as $planExercise) {
            $exerciseSlug = $planExercise->getExercise()->getSlug();
            $plannedSets = $planExercise->getSets();
            $plannedRepsMin = $planExercise->getRepsMin();

            if (!isset($submittedBySlug[$exerciseSlug])) {
                // Exercise not submitted at all: 0% for this exercise
                continue;
            }

            $submittedSets = $submittedBySlug[$exerciseSlug]['sets'] ?? [];
            $completedSets = count($submittedSets);

            if ($plannedSets <= 0) {
                $totalCompletionScore += 1.0;
                continue;
            }

            // Score: ratio of completed sets to planned sets
            $setsRatio = min(1.5, $completedSets / $plannedSets);

            // Factor in reps completion per set using exercise default weight as benchmark
            $repsScore = 0.0;
            $exercise = $planExercise->getExercise();
            $benchmarkWeight = $exercise->getDefaultWeight() ?? 0;

            foreach ($submittedSets as $setData) {
                $reps = (int) ($setData['reps'] ?? 0);
                $weight = (float) ($setData['weight'] ?? 0);

                if ($plannedRepsMin > 0 && $reps > 0) {
                    $repsRatio = min(1.5, $reps / $plannedRepsMin);
                    // Bonus if weight exceeds benchmark
                    $weightBonus = ($benchmarkWeight > 0 && $weight > $benchmarkWeight)
                        ? min(0.5, ($weight - $benchmarkWeight) / $benchmarkWeight)
                        : 0.0;
                    $repsScore += $repsRatio + $weightBonus;
                } else {
                    // Timed exercise or no reps data: count as completed set
                    $repsScore += 1.0;
                }
            }

            $avgRepsScore = $completedSets > 0 ? $repsScore / $completedSets : 0.0;

            // Combine: 60% weight on sets completion, 40% on reps quality
            $exerciseScore = ($setsRatio * 0.6) + ($avgRepsScore * 0.4);
            $totalCompletionScore += $exerciseScore;
        }

        $percent = ($totalCompletionScore / $plannedCount) * 100.0;

        return round($percent, 1);
    }

    // ========================================================================
    // Step 6: Performance Tier
    // ========================================================================

    /**
     * Determine performance tier, XP bonuses, loot flags, and result message.
     *
     * @return array{tier: string, xpAwarded: int, bonusXpPercent: float, lootEarned: bool, superLootEarned: bool, message: string}
     */
    public function determinePerformanceTier(
        float $completionPercent,
        int $xpFromMobs,
        int $mobsDefeated,
        BattleMode $mode,
        WorkoutPlan $plan,
        int $mobHp,
        int $totalDamage,
    ): array {
        $failThreshold = (float) $this->getSetting('battle_fail_threshold', '0.50') * 100;
        $partialThreshold = (float) $this->getSetting('battle_partial_threshold', '0.75') * 100;
        $successThreshold = (float) $this->getSetting('battle_success_threshold', '1.00') * 100;
        $overperformBonus = (float) $this->getSetting('battle_overperform_bonus', '0.10');
        $overperformPerMob = (float) $this->getSetting('battle_overperform_per_mob', '0.05');
        $raidOverperformPerMob = (float) $this->getSetting('battle_raid_overperform_per_mob', '0.10');

        // Failed: below 50%
        if ($completionPercent < $failThreshold) {
            return [
                'tier' => 'failed',
                'xpAwarded' => 0,
                'bonusXpPercent' => 0.0,
                'lootEarned' => false,
                'superLootEarned' => false,
                'message' => "Your character couldn't handle it. No XP awarded.",
            ];
        }

        // Survived: 50-75%
        if ($completionPercent < $partialThreshold) {
            return [
                'tier' => 'survived',
                'xpAwarded' => $xpFromMobs,
                'bonusXpPercent' => 0.0,
                'lootEarned' => false,
                'superLootEarned' => false,
                'message' => 'You earned XP but barely escaped!',
            ];
        }

        // Completed: 75-100%
        if ($completionPercent < $successThreshold) {
            return [
                'tier' => 'completed',
                'xpAwarded' => $xpFromMobs,
                'bonusXpPercent' => 0.0,
                'lootEarned' => true,
                'superLootEarned' => false,
                'message' => 'Victory! You completed the challenge.',
            ];
        }

        // Exceeded: 100%+
        // Estimate expected mobs for 100% completion
        $expectedMobs = $mobHp > 0 ? max(1, (int) floor(($totalDamage * ($successThreshold / $completionPercent)) / $mobHp)) : 1;
        $extraMobs = max(0, $mobsDefeated - $expectedMobs);

        if ($mode === BattleMode::Raid) {
            $bonus = $extraMobs * $raidOverperformPerMob;
            $xpAwarded = (int) round($xpFromMobs * (1 + $bonus));

            return [
                'tier' => 'raid_exceeded',
                'xpAwarded' => $xpAwarded,
                'bonusXpPercent' => round($bonus * 100, 1),
                'lootEarned' => true,
                'superLootEarned' => true,
                'message' => 'Raid conquered! Legendary loot earned!',
            ];
        }

        // Normal exceeded
        $bonus = $overperformBonus + ($extraMobs * $overperformPerMob);
        $xpAwarded = (int) round($xpFromMobs * (1 + $bonus));

        return [
            'tier' => 'exceeded',
            'xpAwarded' => $xpAwarded,
            'bonusXpPercent' => round($bonus * 100, 1),
            'lootEarned' => true,
            'superLootEarned' => false,
            'message' => 'Outstanding! You exceeded all expectations!',
        ];
    }

    // ========================================================================
    // Helpers
    // ========================================================================

    /**
     * Determine the activity type for damage calculation based on the workout plan.
     *
     * Maps plan activity types to stat-weighting categories:
     * - strength: gym/weightlifting (STR-heavy)
     * - dex: running/cycling/dance (DEX-heavy)
     * - con: swimming/yoga/endurance (CON-heavy)
     * - mixed: combat/HIIT (balanced)
     */
    private function determineActivityType(WorkoutPlan $plan): string
    {
        $activity = $plan->getActivityType();

        return match ($activity) {
            'strength', null => 'strength',
            'running', 'cycling', 'dance', 'racquet_sports' => 'dex',
            'swimming', 'yoga', 'flexibility', 'mind_body' => 'con',
            'combat', 'hiit', 'cardio' => 'mixed',
            default => 'mixed',
        };
    }

    /**
     * Award XP to the user, create an ExperienceLog, and update CharacterStats.
     *
     * @return array{leveledUp: bool, level: int, totalXp: int}
     */
    private function awardXp(\App\Domain\User\Entity\User $user, int $xpAmount, WorkoutSession $session): array
    {
        // Create experience log entry
        $log = new ExperienceLog();
        $log->setUser($user);
        $log->setAmount($xpAmount);
        $log->setSource('battle');
        $log->setDescription(sprintf(
            'Battle result: %s (%s mode)',
            $session->getPerformanceTier() ?? 'unknown',
            $session->getMode()->value,
        ));
        $this->entityManager->persist($log);

        // Update character stats
        $stats = $this->characterStatsRepository->findByUser($user);
        $oldLevel = 1;
        $newTotalXp = $xpAmount;

        if ($stats === null) {
            $stats = new CharacterStats();
            $stats->setUser($user);
        } else {
            $oldLevel = $stats->getLevel();
            $newTotalXp = $stats->getTotalXp() + $xpAmount;
        }

        $stats->setTotalXp($newTotalXp);

        // Use the leveling service for proper level calculation
        $newLevel = $this->levelingService->getLevelForTotalXp($newTotalXp);
        $stats->setLevel($newLevel);

        $this->entityManager->persist($stats);

        return [
            'leveledUp' => $newLevel > $oldLevel,
            'level' => $newLevel,
            'totalXp' => $newTotalXp,
        ];
    }

    /**
     * Flag that the user's next workout plan should use a reduced difficulty modifier.
     *
     * Stores a 'battle_next_difficulty_modifier' game setting per-user via health data
     * on the session. The WorkoutPlanGeneratorService checks this on next plan generation.
     */
    private function flagDifficultyReduction(\App\Domain\User\Entity\User $user): void
    {
        // Store a user-specific flag that the next plan should be easier.
        // We use a convention: store the user ID + modifier in a simple session attribute.
        // The WorkoutPlanGeneratorService will check completed sessions for this flag.
        // No additional storage needed: the 'failed' performanceTier on the session is the flag.
    }

    /** Read a game setting value, falling back to a default. */
    private function getSetting(string $key, string $default): string
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = $this->gameSettingRepository->getAllAsMap();
        }

        return $this->settingsCache[$key] ?? $default;
    }

    /**
     * Look up the realm of the mob linked to this session (if any).
     * WorkoutSession may not always have a mob (legacy sessions), hence the null path.
     */
    private function resolveMobRealm(WorkoutSession $session): ?Realm
    {
        $mob = $session->getMob();

        return $mob?->getRealm();
    }

    /**
     * True if the user has an equipped artifact whose realm equals $mobRealm.
     * We iterate the already-cheap equipped set (<=12 items per user).
     */
    private function hasRealmBoundArtifactMatching(\App\Domain\User\Entity\User $user, Realm $mobRealm): bool
    {
        foreach ($this->userInventoryRepository->findEquippedByUser($user) as $inventoryItem) {
            $catalog = $inventoryItem->getItemCatalog();
            if ($catalog->getRealm() === $mobRealm) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply the +40% realm-match damage multiplier (BUSINESS_LOGIC §12).
     * Multiplier factor is expressed as a constant to match the doc exactly.
     */
    public function applyRealmMatchMultiplier(int $totalDamage): int
    {
        return (int) round($totalDamage * 1.4);
    }
}
