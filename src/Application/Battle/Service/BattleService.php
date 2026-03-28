<?php

declare(strict_types=1);

namespace App\Application\Battle\Service;

use App\Domain\Battle\Entity\WorkoutSession;
use App\Domain\Battle\Enum\BattleMode;
use App\Domain\Battle\Enum\SessionStatus;
use App\Domain\Character\Entity\CharacterStats;
use App\Domain\Character\Entity\ExperienceLog;
use App\Domain\User\Entity\User;
use App\Domain\Workout\Entity\WorkoutPlan;
use App\Domain\Workout\Entity\WorkoutPlanExercise;
use App\Domain\Workout\Entity\WorkoutPlanExerciseLog;
use App\Domain\Workout\Enum\WorkoutPlanStatus;
use App\Infrastructure\Battle\Repository\WorkoutSessionRepository;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use App\Infrastructure\Workout\Repository\ExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Core battle orchestration service handling session lifecycle and XP awards.
 *
 * Application layer (Battle bounded context). Manages the full battle flow:
 * starting a session (with mob selection), processing exercise submissions
 * to calculate damage, determining whether the mob is defeated, awarding
 * XP proportionally or fully, and completing or abandoning sessions.
 *
 * Damage formula:
 * - Strength exercises: sum(reps * weight * 0.1) per set
 * - Cardio/timed exercises: sum(duration * 0.5) per set
 */
class BattleService
{
    /** Damage coefficient for strength-based exercises (reps * weight * coefficient). */
    private const STRENGTH_DAMAGE_COEFFICIENT = 0.1;

    /** Damage coefficient for timed/cardio exercises (duration * coefficient). */
    private const CARDIO_DAMAGE_COEFFICIENT = 0.5;

    public function __construct(
        private readonly WorkoutSessionRepository $sessionRepository,
        private readonly BattleMobService $battleMobService,
        private readonly ExerciseRepository $exerciseRepository,
        private readonly CharacterStatsRepository $characterStatsRepository,
        private readonly ExperienceLogRepository $experienceLogRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Start a new battle session for the user.
     *
     * If an active session already exists, it will be abandoned first.
     * Selects an appropriate mob, sets the workout plan to in_progress,
     * and creates a new active WorkoutSession.
     */
    public function startBattle(User $user, WorkoutPlan $plan, BattleMode $mode): WorkoutSession
    {
        // Abandon any existing active session
        $existing = $this->sessionRepository->findActiveByUser($user);
        if ($existing !== null) {
            $this->abandonBattle($existing);
        }

        // Select a mob for this battle
        $mobData = $this->battleMobService->selectMob($user, $mode);

        // Set the workout plan to in_progress
        $plan->setStatus(WorkoutPlanStatus::InProgress);
        $plan->setStartedAt(new \DateTimeImmutable());

        // Create the session
        $session = new WorkoutSession();
        $session->setUser($user);
        $session->setWorkoutPlan($plan);
        $session->setMode($mode);
        $session->setMob($mobData['mob']);
        $session->setMobHp($mobData['hp']);
        $session->setMobXpReward($mobData['xpReward']);

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        return $session;
    }

    /**
     * Complete a battle session by processing exercise logs and awarding XP.
     *
     * Calculates damage from each exercise set, determines if the mob was defeated,
     * awards full or partial XP accordingly, and completes both session and plan.
     * Accepts optional skill and consumable slugs used during the session.
     *
     * @param WorkoutSession $session           The active session to complete
     * @param array          $exercises         Exercise data with sets from the mobile app
     * @param array|null     $healthData        Health API data (duration, calories, heart rate)
     * @param string[]       $usedSkills        Skill slugs activated during the session
     * @param string[]       $usedConsumables   Consumable item slugs used during the session
     *
     * @return array{xpAwarded: int, mobDefeated: bool, damageDealt: int, rewardTier: string, levelUp: bool, newLevel: int, totalXp: int, mobsDefeated: int, xpFromMobs: int, xpFromExercises: int, session: WorkoutSession}
     */
    public function completeBattle(
        WorkoutSession $session,
        array $exercises,
        ?array $healthData,
        array $usedSkills = [],
        array $usedConsumables = [],
    ): array
    {
        // Store used skills and consumables on the session entity
        if (!empty($usedSkills)) {
            $session->setUsedSkillSlugs($usedSkills);
        }
        if (!empty($usedConsumables)) {
            $session->setUsedConsumableSlugs($usedConsumables);
        }

        $totalDamage = 0;

        // Process each submitted exercise and its sets
        foreach ($exercises as $exerciseData) {
            $slug = $exerciseData['exerciseSlug'] ?? null;
            if ($slug === null) {
                continue;
            }

            $exercise = $this->exerciseRepository->findBySlug($slug);
            if ($exercise === null) {
                continue;
            }

            // Find or create a WorkoutPlanExercise for this exercise in the plan
            $planExercise = $this->findOrCreatePlanExercise($session->getWorkoutPlan(), $exercise);

            $sets = $exerciseData['sets'] ?? [];
            foreach ($sets as $setData) {
                $reps = (int) ($setData['reps'] ?? 0);
                $weight = (float) ($setData['weight'] ?? 0);
                $duration = (int) ($setData['duration'] ?? 0);
                $setNumber = (int) ($setData['setNumber'] ?? 1);

                // Create a log entry for each set
                $log = new WorkoutPlanExerciseLog();
                $log->setPlanExercise($planExercise);
                $log->setSetNumber($setNumber);
                $log->setReps($reps > 0 ? $reps : null);
                $log->setWeight($weight > 0 ? $weight : null);
                $log->setDuration($duration > 0 ? $duration : null);
                $this->entityManager->persist($log);

                // Calculate damage for this set
                $totalDamage += $this->calculateSetDamage($reps, $weight, $duration);
            }
        }

        // Store health data on the session
        $session->setHealthData($healthData);
        $session->setTotalDamageDealt($totalDamage);

        // Determine if mob was defeated and calculate XP
        $mobHp = $session->getMobHp() ?? 0;
        $mobXpReward = $session->getMobXpReward() ?? 0;
        $mobDefeated = $mobHp > 0 && $totalDamage >= $mobHp;

        // Calculate XP: full if mob defeated, proportional otherwise
        $xpAwarded = 0;
        $rewardTier = 'none';

        if ($mobDefeated) {
            $xpAwarded = $mobXpReward;
            $rewardTier = $this->determineRewardTier($totalDamage, $mobHp);
            // Bonus XP from reward tiers
            $xpAwarded += $this->getRewardTierBonus($rewardTier, $mobXpReward);
        } elseif ($mobHp > 0) {
            // Partial XP proportional to damage dealt
            $ratio = min(1.0, $totalDamage / $mobHp);
            $xpAwarded = (int) round($mobXpReward * $ratio);
            $rewardTier = 'partial';
        }

        $session->setXpAwarded($xpAwarded);

        // Award XP to the user
        $levelUp = false;
        $newLevel = 1;
        $totalXp = 0;

        if ($xpAwarded > 0) {
            $xpResult = $this->awardXp($session->getUser(), $xpAwarded, $session);
            $levelUp = $xpResult['leveledUp'];
            $newLevel = $xpResult['level'];
            $totalXp = $xpResult['totalXp'];
        } else {
            $stats = $this->characterStatsRepository->findByUser($session->getUser());
            if ($stats !== null) {
                $newLevel = $stats->getLevel();
                $totalXp = $stats->getTotalXp();
            }
        }

        // Complete the session
        $session->setStatus(SessionStatus::Completed);
        $session->setCompletedAt(new \DateTimeImmutable());

        // Complete the workout plan
        $plan = $session->getWorkoutPlan();
        $plan->setStatus(WorkoutPlanStatus::Completed);
        $plan->setCompletedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        // Separate XP sources: mob XP accumulated during session vs exercise-based XP
        $xpFromMobs = $session->getTotalXpFromMobs();
        $xpFromExercises = $xpAwarded - $xpFromMobs;
        if ($xpFromExercises < 0) {
            $xpFromExercises = 0;
        }

        return [
            'xpAwarded' => $xpAwarded,
            'mobDefeated' => $mobDefeated,
            'damageDealt' => $totalDamage,
            'rewardTier' => $rewardTier,
            'levelUp' => $levelUp,
            'newLevel' => $newLevel,
            'totalXp' => $totalXp,
            'mobsDefeated' => $session->getMobsDefeated(),
            'xpFromMobs' => $xpFromMobs,
            'xpFromExercises' => $xpFromExercises,
            'session' => $session,
        ];
    }

    /**
     * Record a mob defeat during an active session and select the next mob.
     *
     * Adds the current mob's XP to the session total, increments the defeated
     * counter, selects a new mob via BattleMobService, and updates the session.
     *
     * @return array{mob: array|null, mobsDefeatedSoFar: int, xpFromMobsSoFar: int}
     */
    public function defeatMobAndGetNext(WorkoutSession $session): array
    {
        // Add current mob's XP reward to running total
        $currentMobXp = $session->getMobXpReward() ?? 0;
        $session->setTotalXpFromMobs($session->getTotalXpFromMobs() + $currentMobXp);

        // Increment mobs defeated counter
        $session->setMobsDefeated($session->getMobsDefeated() + 1);

        // Select a new mob for the next encounter
        $mobData = $this->battleMobService->selectMob($session->getUser(), $session->getMode());

        // Update session with the new mob
        $session->setMob($mobData['mob']);
        $session->setMobHp($mobData['hp']);
        $session->setMobXpReward($mobData['xpReward']);

        $this->entityManager->flush();

        // Build mob response array
        $mobResponse = null;
        if ($mobData['mob'] !== null) {
            $mobResponse = [
                'id' => $mobData['mob']->getId()->toRfc4122(),
                'name' => $mobData['mob']->getName(),
                'hp' => $mobData['hp'],
                'xpReward' => $mobData['xpReward'],
                'level' => $mobData['mob']->getLevel(),
                'rarity' => $mobData['mob']->getRarity()?->value,
                'image' => $mobData['mob']->getImage()?->getPublicUrl(),
            ];
        }

        return [
            'mob' => $mobResponse,
            'mobsDefeatedSoFar' => $session->getMobsDefeated(),
            'xpFromMobsSoFar' => $session->getTotalXpFromMobs(),
        ];
    }

    /**
     * Abandon a battle session without completing it.
     *
     * Sets the session status to abandoned and the plan status to skipped.
     */
    public function abandonBattle(WorkoutSession $session): void
    {
        $session->setStatus(SessionStatus::Abandoned);
        $session->setCompletedAt(new \DateTimeImmutable());

        $plan = $session->getWorkoutPlan();
        $plan->setStatus(WorkoutPlanStatus::Skipped);

        $this->entityManager->flush();
    }

    /**
     * Calculate damage dealt by a single set.
     *
     * Strength: reps * weight * 0.1
     * Cardio/timed: duration * 0.5
     * Bodyweight (reps without weight): reps * 1.0
     */
    public function calculateSetDamage(int $reps, float $weight, int $duration): int
    {
        $damage = 0.0;

        if ($reps > 0 && $weight > 0) {
            // Strength exercise: reps * weight * coefficient
            $damage = $reps * $weight * self::STRENGTH_DAMAGE_COEFFICIENT;
        } elseif ($reps > 0) {
            // Bodyweight exercise: each rep = 1 damage point
            $damage = (float) $reps;
        }

        if ($duration > 0) {
            // Cardio/timed component
            $damage += $duration * self::CARDIO_DAMAGE_COEFFICIENT;
        }

        return (int) round($damage);
    }

    /**
     * Find an existing WorkoutPlanExercise or create one for the given exercise.
     *
     * When the user submits exercises in custom mode, the exercise might not be
     * part of the original plan. This creates an ad-hoc plan exercise entry.
     */
    private function findOrCreatePlanExercise(
        WorkoutPlan $plan,
        \App\Domain\Workout\Entity\Exercise $exercise,
    ): WorkoutPlanExercise {
        // Check if the exercise already exists in the plan
        foreach ($plan->getExercises() as $planExercise) {
            if ($planExercise->getExercise()->getId()->toRfc4122() === $exercise->getId()->toRfc4122()) {
                return $planExercise;
            }
        }

        // Create a new plan exercise entry
        $planExercise = new WorkoutPlanExercise();
        $planExercise->setWorkoutPlan($plan);
        $planExercise->setExercise($exercise);
        $planExercise->setOrderIndex($plan->getExercises()->count() + 1);
        $planExercise->setSets($exercise->getDefaultSets());
        $planExercise->setRepsMin($exercise->getDefaultRepsMin());
        $planExercise->setRepsMax($exercise->getDefaultRepsMax());
        $planExercise->setRestSeconds($exercise->getDefaultRestSeconds());

        $plan->addExercise($planExercise);
        $this->entityManager->persist($planExercise);

        return $planExercise;
    }

    /**
     * Determine the reward tier based on overkill ratio (damage vs mob HP).
     *
     * bronze: just defeated (100-119% damage)
     * silver: solid performance (120-149% damage)
     * gold: dominant performance (150%+ damage)
     */
    private function determineRewardTier(int $totalDamage, int $mobHp): string
    {
        if ($mobHp <= 0) {
            return 'none';
        }

        $ratio = $totalDamage / $mobHp;

        if ($ratio >= 1.5) {
            return 'gold';
        }
        if ($ratio >= 1.2) {
            return 'silver';
        }

        return 'bronze';
    }

    /** Calculate bonus XP awarded for reaching a reward tier. */
    private function getRewardTierBonus(string $tier, int $baseXp): int
    {
        return match ($tier) {
            'gold' => (int) round($baseXp * 0.3),
            'silver' => (int) round($baseXp * 0.15),
            'bronze' => 0,
            default => 0,
        };
    }

    /**
     * Award XP to the user by creating an ExperienceLog entry and updating CharacterStats.
     *
     * @return array{leveledUp: bool, level: int, totalXp: int}
     */
    private function awardXp(User $user, int $xpAmount, WorkoutSession $session): array
    {
        // Create experience log entry
        $log = new ExperienceLog();
        $log->setUser($user);
        $log->setAmount($xpAmount);
        $log->setSource('battle');
        $log->setDescription(sprintf('Battle session: %s', $session->getMode()->value));
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

        // Simple level calculation: level increases every 100 XP (placeholder)
        // In production, this would use the LevelingService
        $newLevel = max(1, (int) floor($newTotalXp / 100) + 1);
        $stats->setLevel($newLevel);

        $this->entityManager->persist($stats);

        return [
            'leveledUp' => $newLevel > $oldLevel,
            'level' => $newLevel,
            'totalXp' => $newTotalXp,
        ];
    }
}
