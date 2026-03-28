<?php

declare(strict_types=1);

namespace App\Application\Workout\Service;

use App\Domain\Config\Entity\GameSetting;
use App\Domain\Training\Entity\WorkoutLog;
use App\Domain\User\Entity\User;
use App\Domain\User\Entity\UserTrainingPreference;
use App\Domain\User\Enum\TrainingFrequency;
use App\Domain\Workout\Entity\Exercise;
use App\Domain\Workout\Entity\SplitTemplate;
use App\Domain\Workout\Entity\WorkoutPlan;
use App\Domain\Workout\Entity\WorkoutPlanExercise;
use App\Domain\Workout\Enum\ExerciseDifficulty;
use App\Domain\Workout\Enum\MuscleGroup;
use App\Domain\Workout\Enum\SplitType;
use App\Domain\Workout\Enum\WorkoutPlanStatus;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\Battle\Repository\WorkoutSessionRepository;
use App\Infrastructure\Training\Repository\WorkoutLogRepository;
use App\Infrastructure\User\Repository\UserTrainingPreferenceRepository;
use App\Infrastructure\Workout\Repository\ExerciseRepository;
use App\Infrastructure\Workout\Repository\SplitTemplateRepository;
use App\Infrastructure\Workout\Repository\WorkoutPlanExerciseRepository;
use App\Infrastructure\Workout\Repository\WorkoutPlanRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Generates personalized workout plans based on user preferences and training history.
 *
 * This is the core workout generation engine for the RPG fitness app. It supports multiple
 * activity categories (strength, running, cycling, swimming, yoga, combat, HIIT, and more)
 * and creates WorkoutPlan entities with appropriate exercises, targets, and reward tiers.
 *
 * Algorithm overview:
 * 1. Determine activity type from explicit parameter or user training preferences.
 * 2. For strength workouts:
 *    - Select a training split template based on user's weekly training frequency.
 *    - Determine which day in the split cycle to train (avoiding recently trained muscle groups).
 *    - Apply synergy rules (e.g. chest day also trains triceps).
 *    - Select 6-8 exercises sorted by priority (compounds first, isolation last).
 *    - Vary exercises to avoid repetition from the last session of the same muscle group.
 * 3. For cardio/endurance activities (running, cycling):
 *    - Calculate progressive targets based on recent performance data.
 *    - Apply safe progression rate (max +10% per week).
 * 4. For all activities: set bronze/silver/gold reward tiers for XP awards.
 *
 * Configuration parameters are loaded from GameSetting entities for easy tuning via admin panel.
 */
final class WorkoutPlanGeneratorService
{
    /**
     * Synergy map: when training a primary muscle group, also include these secondary groups.
     * This mimics real-world training wisdom (push muscles together, pull muscles together).
     */
    private const SYNERGY_MAP = [
        'chest' => ['triceps'],
        'back' => ['biceps'],
        'quads' => ['shoulders'],
        'hamstrings' => ['shoulders'],
        'glutes' => ['shoulders'],
    ];

    /**
     * Maps training frequency to the number of training days per week.
     * Used to select the appropriate split template.
     */
    private const FREQUENCY_TO_DAYS = [
        'none' => 3,
        'light' => 3,
        'moderate' => 4,
        'heavy' => 5,
    ];

    /**
     * Maps days-per-week to the preferred split type slug for template lookup.
     * 1-2 days: full body (hit everything each session)
     * 3 days: push/pull/legs (classic 3-day split)
     * 4 days: upper/lower (allows more volume per muscle group)
     * 5+ days: push/pull/legs (doubled or with accessories)
     */
    private const DAYS_TO_SPLIT = [
        1 => 'full_body',
        2 => 'full_body',
        3 => 'push_pull_legs',
        4 => 'upper_lower',
        5 => 'push_pull_legs',
        6 => 'push_pull_legs',
    ];

    public function __construct(
        private readonly ExerciseRepository $exerciseRepository,
        private readonly SplitTemplateRepository $splitTemplateRepository,
        private readonly WorkoutPlanRepository $workoutPlanRepository,
        private readonly WorkoutPlanExerciseRepository $workoutPlanExerciseRepository,
        private readonly GameSettingRepository $gameSettingRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserTrainingPreferenceRepository $trainingPreferenceRepository,
        private readonly WorkoutLogRepository $workoutLogRepository,
        private readonly WorkoutSessionRepository $workoutSessionRepository,
    ) {
    }

    /**
     * Generate a personalized workout plan for the given user.
     *
     * The activity category determines the plan type: null or 'strength' creates a gym plan
     * based on the user's split template, while other categories (running, swimming, yoga, etc.)
     * create activity-specific plans with duration/distance targets.
     *
     * @param User                   $user             The user to generate a plan for
     * @param string|null            $activityCategory Activity slug or null for auto-detect
     * @param \DateTimeImmutable|null $date            When the workout is planned (defaults to today)
     *
     * @return WorkoutPlan The persisted workout plan with exercises and reward tiers
     */
    public function generatePlan(User $user, ?string $activityCategory = null, ?\DateTimeImmutable $date = null): WorkoutPlan
    {
        $date = $date ?? new \DateTimeImmutable('today');

        // Auto-detect activity category from user preferences if not provided
        if ($activityCategory === null) {
            $activityCategory = $this->detectActivityCategory($user);
        }

        // Route to the appropriate plan generator based on activity category
        $plan = match ($activityCategory) {
            'strength', null => $this->generateStrengthPlan($user, $date),
            'running' => $this->generateRunningPlan($user, $date),
            'cycling' => $this->generateCyclingPlan($user, $date),
            'swimming' => $this->generateSwimmingPlan($user, $date),
            'flexibility', 'yoga' => $this->generateYogaPlan($user, $date),
            'combat' => $this->generateCombatPlan($user, $date),
            'cardio', 'hiit' => $this->generateHiitPlan($user, $date),
            default => $this->generateGenericActivityPlan($user, $activityCategory, $date),
        };

        // Apply difficulty reduction if the user's last session was 'failed'
        $difficultyModifier = $this->getDifficultyModifierForUser($user);
        if ($difficultyModifier < 1.0) {
            $plan->setDifficultyModifier($difficultyModifier);
        }

        return $plan;
    }

    /**
     * Check the user's most recent completed session to determine difficulty adjustment.
     *
     * If the last session result was 'failed', returns 0.8 (20% easier). Otherwise 1.0.
     */
    private function getDifficultyModifierForUser(User $user): float
    {
        $recentSessions = $this->workoutSessionRepository->findByUser($user, 1);
        if (empty($recentSessions)) {
            return 1.0;
        }

        $lastSession = $recentSessions[0];
        if ($lastSession->getPerformanceTier() === 'failed') {
            return 0.8;
        }

        return 1.0;
    }

    /**
     * Generate a strength training plan using the split template system.
     *
     * Steps:
     * 1. Determine training frequency from user preferences.
     * 2. Select the appropriate split template (full_body, PPL, upper_lower).
     * 3. Find which day in the split cycle hasn't been trained recently.
     * 4. Get target muscle groups and apply synergy rules.
     * 5. Select exercises (compounds first, then isolation) respecting difficulty.
     * 6. Create WorkoutPlan and WorkoutPlanExercise entities.
     */
    private function generateStrengthPlan(User $user, \DateTimeImmutable $date): WorkoutPlan
    {
        $settings = $this->gameSettingRepository->getAllAsMap();
        $exercisesPerSession = (int) ($settings['workout_exercises_per_session'] ?? 7);

        // Step 1: Determine how many days per week the user trains
        $daysPerWeek = $this->getTrainingDaysPerWeek($user);

        // Step 2: Select the split template matching the user's frequency
        $splitType = self::DAYS_TO_SPLIT[$daysPerWeek] ?? 'push_pull_legs';
        $template = $this->splitTemplateRepository->findBySlug($splitType);

        // Fallback: if no template found, use first available or build a simple full-body plan
        if ($template === null) {
            $templates = $this->splitTemplateRepository->findByDaysPerWeek($daysPerWeek);
            $template = $templates[0] ?? null;
        }

        // Step 3: Determine which day in the split cycle to use
        $dayConfig = $this->selectSplitDay($user, $template);

        // Extract muscle groups from the day config
        $muscleGroupStrings = $dayConfig['muscleGroups'] ?? ['chest', 'triceps'];
        $dayName = $dayConfig['name'] ?? 'Workout';

        // Step 4: Apply synergy rules to expand muscle group targets
        $allMuscleGroups = $this->applySynergyRules($muscleGroupStrings);

        // Step 5: Get the user's difficulty level for exercise filtering
        $difficulty = $this->getUserDifficulty($user);

        // Step 6: Find exercises from recent workouts to exclude (for variety)
        $recentSlugs = $this->getRecentExerciseSlugs($user, $muscleGroupStrings);

        // Step 7: Select exercises matching target muscle groups
        $exercises = $this->selectExercisesForMuscleGroups(
            $allMuscleGroups,
            $difficulty,
            $exercisesPerSession,
            $recentSlugs,
        );

        // Step 8: Build the plan name from muscle groups
        $planName = $dayName . ' - ' . implode(' & ', array_map(
            fn(string $mg) => ucfirst($mg),
            $muscleGroupStrings,
        ));

        // Step 9: Calculate reward tiers for strength workout
        $baseXp = (int) ($settings['workout_base_xp_per_workout'] ?? 100);
        $rewardTiers = $this->getStrengthRewardTiers($baseXp, count($exercises));

        // Step 10: Create and persist the workout plan
        $plan = new WorkoutPlan();
        $plan->setUser($user);
        $plan->setName($planName);
        $plan->setActivityType('strength');
        $plan->setTargetMuscleGroups($muscleGroupStrings);
        $plan->setPlannedAt($date);
        $plan->setTargetDuration(60);
        $plan->setRewardTiers($rewardTiers);

        $this->entityManager->persist($plan);

        // Step 11: Create WorkoutPlanExercise entries for each selected exercise
        $orderIndex = 1;
        foreach ($exercises as $exercise) {
            $planExercise = new WorkoutPlanExercise();
            $planExercise->setWorkoutPlan($plan);
            $planExercise->setExercise($exercise);
            $planExercise->setOrderIndex($orderIndex);
            $planExercise->setSets($exercise->getDefaultSets());
            $planExercise->setRepsMin($exercise->getDefaultRepsMin());
            $planExercise->setRepsMax($exercise->getDefaultRepsMax());
            $planExercise->setRestSeconds($exercise->getDefaultRestSeconds());

            $plan->addExercise($planExercise);
            $this->entityManager->persist($planExercise);
            $orderIndex++;
        }

        $this->entityManager->flush();

        return $plan;
    }

    /**
     * Generate a running plan with progressive distance targets.
     *
     * Analyzes the user's recent running data (last 14 days) and applies a safe
     * progression rate (default 10% per week). Sets bronze/silver/gold tiers
     * based on percentage of target distance achieved.
     */
    private function generateRunningPlan(User $user, \DateTimeImmutable $date): WorkoutPlan
    {
        $runningTarget = $this->calculateRunningTarget($user);

        $plan = new WorkoutPlan();
        $plan->setUser($user);
        $plan->setName('Running Session');
        $plan->setActivityType('running');
        $plan->setPlannedAt($date);
        $plan->setTargetDistance($runningTarget['distance']);
        $plan->setTargetDuration($runningTarget['duration']);
        $plan->setRewardTiers($this->getRewardTiers('running', $runningTarget['distance']));

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        return $plan;
    }

    /**
     * Generate a cycling plan with distance and speed targets.
     * Similar to running but with different metrics and longer default distances.
     */
    private function generateCyclingPlan(User $user, \DateTimeImmutable $date): WorkoutPlan
    {
        // Cycling distances are typically 3-5x longer than running
        $runningTarget = $this->calculateRunningTarget($user);
        $cyclingDistance = $runningTarget['distance'] * 3.0;

        $plan = new WorkoutPlan();
        $plan->setUser($user);
        $plan->setName('Cycling Session');
        $plan->setActivityType('cycling');
        $plan->setPlannedAt($date);
        $plan->setTargetDistance($cyclingDistance);
        $plan->setTargetDuration((int) ($runningTarget['duration'] * 1.5));
        $plan->setRewardTiers($this->getRewardTiers('cycling', $cyclingDistance));

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        return $plan;
    }

    /**
     * Generate a swimming plan with structured laps and drills.
     *
     * Selects swimming exercises (freestyle, backstroke, drills) from the exercise catalog
     * and creates a plan with target duration. Plans are structured with warm-up,
     * main set, and cool-down phases.
     */
    private function generateSwimmingPlan(User $user, \DateTimeImmutable $date): WorkoutPlan
    {
        $settings = $this->gameSettingRepository->getAllAsMap();
        $exercisesPerSession = (int) ($settings['workout_exercises_per_session'] ?? 7);
        $difficulty = $this->getUserDifficulty($user);

        // Select swimming-specific exercises from the catalog
        $exercises = $this->selectExercisesByCategory('swimming', $difficulty, $exercisesPerSession);

        // Duration scales with difficulty: beginner 20min, intermediate 35min, advanced 50min
        $duration = match ($difficulty) {
            'beginner' => 20,
            'intermediate' => 35,
            default => 50,
        };

        $baseXp = (int) ($settings['workout_base_xp_per_workout'] ?? 100);

        $plan = new WorkoutPlan();
        $plan->setUser($user);
        $plan->setName('Swimming Session');
        $plan->setActivityType('swimming');
        $plan->setPlannedAt($date);
        $plan->setTargetDuration($duration);
        $plan->setRewardTiers($this->getRewardTiers('swimming', (float) $duration));

        $this->entityManager->persist($plan);

        $this->attachExercisesToPlan($plan, $exercises);
        $this->entityManager->flush();

        return $plan;
    }

    /**
     * Generate a yoga/flexibility plan ordered by sequence phases.
     *
     * Exercises are ordered by priority which maps to yoga phases:
     * 1 = warm-up, 2 = standing poses, 3 = balance, 4 = floor poses, 5 = cool-down/savasana.
     *
     * Duration depends on difficulty: beginner 20min, intermediate 40min, advanced 60min.
     */
    private function generateYogaPlan(User $user, \DateTimeImmutable $date): WorkoutPlan
    {
        $settings = $this->gameSettingRepository->getAllAsMap();
        $exercisesPerSession = (int) ($settings['workout_exercises_per_session'] ?? 7);
        $difficulty = $this->getUserDifficulty($user);

        $exercises = $this->selectExercisesByCategory('yoga', $difficulty, $exercisesPerSession);

        // Duration scales with difficulty: beginner 20min, intermediate 40min, advanced 60min
        $duration = match ($difficulty) {
            'beginner' => 20,
            'intermediate' => 40,
            default => 60,
        };

        $plan = new WorkoutPlan();
        $plan->setUser($user);
        $plan->setName('Yoga & Flexibility Session');
        $plan->setActivityType('yoga');
        $plan->setPlannedAt($date);
        $plan->setTargetDuration($duration);
        $plan->setRewardTiers($this->getRewardTiers('yoga', (float) $duration));

        $this->entityManager->persist($plan);

        $this->attachExercisesToPlan($plan, $exercises);
        $this->entityManager->flush();

        return $plan;
    }

    /**
     * Generate a combat training plan structured as rounds.
     *
     * Combat plans follow a round-based structure: 3 minutes of work followed by
     * 1 minute of rest. Exercises are mixed between different combat techniques
     * (striking, kicks, combinations, defensive drills).
     */
    private function generateCombatPlan(User $user, \DateTimeImmutable $date): WorkoutPlan
    {
        $settings = $this->gameSettingRepository->getAllAsMap();
        $exercisesPerSession = (int) ($settings['workout_exercises_per_session'] ?? 7);
        $difficulty = $this->getUserDifficulty($user);

        $exercises = $this->selectExercisesByCategory('combat', $difficulty, $exercisesPerSession);

        // Combat rounds: beginner 6 rounds (~24min), intermediate 8 (~32min), advanced 10 (~40min)
        $duration = match ($difficulty) {
            'beginner' => 24,
            'intermediate' => 32,
            default => 40,
        };

        $plan = new WorkoutPlan();
        $plan->setUser($user);
        $plan->setName('Combat Training');
        $plan->setActivityType('combat');
        $plan->setPlannedAt($date);
        $plan->setTargetDuration($duration);
        $plan->setRewardTiers($this->getRewardTiers('combat', (float) $duration));

        $this->entityManager->persist($plan);

        // Set combat-specific rest/work intervals on each exercise
        $orderIndex = 1;
        foreach ($exercises as $exercise) {
            $planExercise = new WorkoutPlanExercise();
            $planExercise->setWorkoutPlan($plan);
            $planExercise->setExercise($exercise);
            $planExercise->setOrderIndex($orderIndex);
            $planExercise->setSets(3); // 3 rounds per exercise
            $planExercise->setRepsMin($exercise->getDefaultRepsMin());
            $planExercise->setRepsMax($exercise->getDefaultRepsMax());
            $planExercise->setRestSeconds(60); // 1 minute rest between rounds
            $planExercise->setNotes('3 min round, 1 min rest');

            $plan->addExercise($planExercise);
            $this->entityManager->persist($planExercise);
            $orderIndex++;
        }

        $this->entityManager->flush();

        return $plan;
    }

    /**
     * Generate a HIIT/cardio circuit plan.
     *
     * Structured as a circuit: 30 seconds work, 15 seconds rest per exercise,
     * repeated for N rounds. Progressive: more rounds as user advances.
     */
    private function generateHiitPlan(User $user, \DateTimeImmutable $date): WorkoutPlan
    {
        $settings = $this->gameSettingRepository->getAllAsMap();
        $exercisesPerSession = (int) ($settings['workout_exercises_per_session'] ?? 7);
        $difficulty = $this->getUserDifficulty($user);

        $exercises = $this->selectExercisesByCategory('hiit', $difficulty, $exercisesPerSession);

        // If no HIIT-specific exercises, fall back to cardio category
        if (empty($exercises)) {
            $exercises = $this->selectExercisesByCategory('cardio', $difficulty, $exercisesPerSession);
        }

        // Rounds scale with difficulty: beginner 3, intermediate 4, advanced 5
        $rounds = match ($difficulty) {
            'beginner' => 3,
            'intermediate' => 4,
            default => 5,
        };

        // Duration: exercises * 0.75min per exercise per round * rounds
        $duration = (int) (count($exercises) * 0.75 * $rounds);
        $duration = max($duration, 15); // Minimum 15 minutes

        $plan = new WorkoutPlan();
        $plan->setUser($user);
        $plan->setName('HIIT Circuit');
        $plan->setActivityType('hiit');
        $plan->setPlannedAt($date);
        $plan->setTargetDuration($duration);
        $plan->setRewardTiers($this->getRewardTiers('hiit', (float) $duration));

        $this->entityManager->persist($plan);

        // Each exercise gets round-based sets with short rest intervals
        $orderIndex = 1;
        foreach ($exercises as $exercise) {
            $planExercise = new WorkoutPlanExercise();
            $planExercise->setWorkoutPlan($plan);
            $planExercise->setExercise($exercise);
            $planExercise->setOrderIndex($orderIndex);
            $planExercise->setSets($rounds);
            $planExercise->setRepsMin($exercise->getDefaultRepsMin());
            $planExercise->setRepsMax($exercise->getDefaultRepsMax());
            $planExercise->setRestSeconds(15); // 15-second rest between exercises in circuit
            $planExercise->setNotes('30 sec work, 15 sec rest');

            $plan->addExercise($planExercise);
            $this->entityManager->persist($planExercise);
            $orderIndex++;
        }

        $this->entityManager->flush();

        return $plan;
    }

    /**
     * Generate a plan for any activity category not specifically handled above.
     *
     * Works for: dance, winter, racquet, team, water, outdoor, mind_body, other, etc.
     * Selects exercises from the specific activity category, sorts by priority,
     * and creates a duration-based plan with standard reward tiers.
     */
    private function generateGenericActivityPlan(User $user, string $activityCategory, \DateTimeImmutable $date): WorkoutPlan
    {
        $settings = $this->gameSettingRepository->getAllAsMap();
        $exercisesPerSession = (int) ($settings['workout_exercises_per_session'] ?? 7);
        $difficulty = $this->getUserDifficulty($user);

        $exercises = $this->selectExercisesByCategory($activityCategory, $difficulty, $exercisesPerSession);

        $duration = match ($difficulty) {
            'beginner' => 30,
            'intermediate' => 45,
            default => 60,
        };

        $planName = ucfirst(str_replace('_', ' ', $activityCategory)) . ' Session';

        $plan = new WorkoutPlan();
        $plan->setUser($user);
        $plan->setName($planName);
        $plan->setActivityType($activityCategory);
        $plan->setPlannedAt($date);
        $plan->setTargetDuration($duration);
        $plan->setRewardTiers($this->getRewardTiers($activityCategory, (float) $duration));

        $this->entityManager->persist($plan);

        $this->attachExercisesToPlan($plan, $exercises);
        $this->entityManager->flush();

        return $plan;
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    /**
     * Select exercises matching the given muscle groups, difficulty, and count.
     *
     * Selection algorithm:
     * 1. Query all exercises for the target muscle groups.
     * 2. Filter by difficulty (beginner exercises for beginners, all for advanced).
     * 3. Exclude recently used exercises for variety.
     * 4. Sort by priority (compounds first, isolation last).
     * 5. Return up to $count exercises.
     *
     * @param string[] $muscleGroups  Target muscle group values
     * @param string   $difficulty    User difficulty level
     * @param int      $count         Maximum number of exercises to return
     * @param string[] $excludeSlugs  Exercise slugs to exclude (for variety)
     *
     * @return Exercise[]
     */
    public function selectExercisesForMuscleGroups(
        array $muscleGroups,
        string $difficulty,
        int $count,
        array $excludeSlugs = [],
    ): array {
        $allExercises = [];
        $difficultyEnum = ExerciseDifficulty::tryFrom($difficulty) ?? ExerciseDifficulty::Beginner;

        // Collect exercises for each target muscle group
        foreach ($muscleGroups as $muscleGroupStr) {
            $muscleGroup = MuscleGroup::tryFrom($muscleGroupStr);
            if ($muscleGroup === null) {
                continue;
            }

            // For beginners, only fetch beginner exercises. For others, fetch matching + lower difficulty.
            if ($difficultyEnum === ExerciseDifficulty::Advanced) {
                // Advanced users can do all exercises
                $exercises = $this->exerciseRepository->findByMuscleGroup($muscleGroup);
            } else {
                // Fetch exercises at the user's difficulty level
                $exercises = $this->exerciseRepository->findByMuscleGroupAndDifficulty($muscleGroup, $difficultyEnum);

                // If not enough exercises found, also include beginner exercises
                if (count($exercises) < $count && $difficultyEnum !== ExerciseDifficulty::Beginner) {
                    $beginnerExercises = $this->exerciseRepository->findByMuscleGroupAndDifficulty(
                        $muscleGroup,
                        ExerciseDifficulty::Beginner,
                    );
                    $exercises = array_merge($exercises, $beginnerExercises);
                }
            }

            // Filter exercises that target strength (no activity category = gym exercise)
            $exercises = array_filter($exercises, fn(Exercise $e) => $e->getActivityCategory() === null);

            $allExercises = array_merge($allExercises, $exercises);
        }

        // Remove duplicates by slug
        $uniqueExercises = [];
        $seenSlugs = [];
        foreach ($allExercises as $exercise) {
            $slug = $exercise->getSlug();
            if (!isset($seenSlugs[$slug])) {
                $seenSlugs[$slug] = true;
                $uniqueExercises[] = $exercise;
            }
        }

        // Exclude recently used exercises for variety
        if (!empty($excludeSlugs)) {
            $excludeMap = array_flip($excludeSlugs);
            $filtered = array_filter(
                $uniqueExercises,
                fn(Exercise $e) => !isset($excludeMap[$e->getSlug()]),
            );

            // Only apply exclusion if we still have enough exercises after filtering
            if (count($filtered) >= $count) {
                $uniqueExercises = array_values($filtered);
            }
        }

        // Sort by priority (1 = compound first, 5 = isolation last)
        usort($uniqueExercises, fn(Exercise $a, Exercise $b) => $a->getPriority() <=> $b->getPriority());

        return array_slice($uniqueExercises, 0, $count);
    }

    /**
     * Select exercises by activity category (swimming, yoga, combat, etc.).
     *
     * @return Exercise[]
     */
    private function selectExercisesByCategory(string $category, string $difficulty, int $count): array
    {
        $difficultyEnum = ExerciseDifficulty::tryFrom($difficulty) ?? ExerciseDifficulty::Beginner;

        // Use query builder since ExerciseRepository doesn't have a category+difficulty finder
        $qb = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(Exercise::class, 'e')
            ->where('e.activityCategory = :category')
            ->setParameter('category', $category)
            ->orderBy('e.priority', 'ASC');

        // Filter by difficulty for non-advanced users
        if ($difficultyEnum !== ExerciseDifficulty::Advanced) {
            $qb->andWhere('e.difficulty IN (:difficulties)')
                ->setParameter('difficulties', [
                    $difficultyEnum->value,
                    ExerciseDifficulty::Beginner->value,
                ]);
        }

        $qb->setMaxResults($count);

        return $qb->getQuery()->getResult();
    }

    /**
     * Calculate running target distance and duration based on recent performance.
     *
     * Looks at the user's running workout logs from the last 14 days, calculates
     * average distance, and applies progressive overload (max +10% per week).
     * If the user has rested 3+ days, suggests a slightly higher target.
     *
     * @return array{distance: float, duration: int}
     */
    public function calculateRunningTarget(User $user): array
    {
        $settings = $this->gameSettingRepository->getAllAsMap();
        $progressionRate = (float) ($settings['workout_cardio_progression_rate'] ?? 0.1);

        // Look at running logs from the last 14 days
        $since = new \DateTimeImmutable('-14 days');
        $recentLogs = $this->workoutLogRepository->findByUser($user);

        // Filter to running logs within the date range
        $runningLogs = array_filter($recentLogs, function (WorkoutLog $log) use ($since) {
            return $log->getWorkoutType() === 'running'
                && $log->getPerformedAt() >= $since
                && $log->getDistance() !== null;
        });

        if (empty($runningLogs)) {
            // No recent running data: use conservative defaults (3km / 30min)
            return ['distance' => 3000.0, 'duration' => 30];
        }

        // Calculate average distance from recent runs
        $totalDistance = 0.0;
        $totalDuration = 0.0;
        $count = 0;
        $lastRunDate = null;

        foreach ($runningLogs as $log) {
            $totalDistance += $log->getDistance();
            $totalDuration += $log->getDurationMinutes();
            $count++;

            if ($lastRunDate === null || $log->getPerformedAt() > $lastRunDate) {
                $lastRunDate = $log->getPerformedAt();
            }
        }

        $avgDistance = $totalDistance / $count;
        $avgDuration = $totalDuration / $count;

        // Apply progressive overload: increase by the progression rate
        $targetDistance = $avgDistance * (1 + $progressionRate);

        // If rested 3+ days, add a small additional bump (user is recovered)
        if ($lastRunDate !== null) {
            $daysSinceLastRun = (int) $lastRunDate->diff(new \DateTimeImmutable())->days;
            if ($daysSinceLastRun >= 3) {
                $targetDistance *= 1.05; // Additional 5% for well-rested users
            }
        }

        // Cap target at reasonable progression (max 10% above average)
        $maxTarget = $avgDistance * (1 + $progressionRate);
        $targetDistance = min($targetDistance, $maxTarget * 1.05);

        $targetDuration = (int) ceil($avgDuration * (1 + $progressionRate));

        return [
            'distance' => round($targetDistance, 1),
            'duration' => max($targetDuration, 15), // Minimum 15 minutes
        ];
    }

    /**
     * Build reward tiers (bronze/silver/gold) for a given activity category and target value.
     *
     * Tiers use configurable multipliers from GameSettings:
     * - Bronze: 60% of target (guaranteed XP for showing up)
     * - Silver: 100% of target (normal XP)
     * - Gold: 130% of target (bonus XP for exceeding the goal)
     *
     * @return array{bronze: array, silver: array, gold: array}
     */
    public function getRewardTiers(string $activityCategory, float $targetValue): array
    {
        $settings = $this->gameSettingRepository->getAllAsMap();
        $baseXp = (int) ($settings['workout_base_xp_per_workout'] ?? 100);
        $bronzeMul = (float) ($settings['workout_reward_bronze_multiplier'] ?? 0.6);
        $silverMul = (float) ($settings['workout_reward_silver_multiplier'] ?? 1.0);
        $goldMul = (float) ($settings['workout_reward_gold_multiplier'] ?? 1.3);

        return [
            'bronze' => [
                'threshold' => round($targetValue * $bronzeMul, 1),
                'xp' => (int) ($baseXp * 0.5),
            ],
            'silver' => [
                'threshold' => round($targetValue * $silverMul, 1),
                'xp' => $baseXp,
            ],
            'gold' => [
                'threshold' => round($targetValue * $goldMul, 1),
                'xp' => (int) ($baseXp * 2),
            ],
        ];
    }

    /**
     * Determine the user's exercise difficulty level from their activity level and history.
     *
     * Maps user activity level to exercise difficulty:
     * - Sedentary/Light -> Beginner
     * - Moderate/Active -> Intermediate
     * - VeryActive -> Advanced
     *
     * Falls back to 'beginner' if no activity level is set.
     */
    public function getUserDifficulty(User $user): string
    {
        $activityLevel = $user->getActivityLevel();

        if ($activityLevel === null) {
            return ExerciseDifficulty::Beginner->value;
        }

        return match ($activityLevel->value) {
            'sedentary', 'light' => ExerciseDifficulty::Beginner->value,
            'moderate', 'active' => ExerciseDifficulty::Intermediate->value,
            'very_active' => ExerciseDifficulty::Advanced->value,
            default => ExerciseDifficulty::Beginner->value,
        };
    }

    /**
     * Get muscle groups trained by the user in the last N days.
     *
     * Queries recent completed/in-progress workout plans and extracts their
     * target muscle groups. Used to determine which day in the split cycle
     * to train next (avoiding muscle groups that were recently worked).
     *
     * @return string[] Array of muscle group values recently trained
     */
    public function getRecentWorkoutMuscleGroups(User $user, int $days = 7): array
    {
        $since = new \DateTimeImmutable("-{$days} days");

        // Query plans from the last N days that were started or completed
        $recentPlans = $this->entityManager->createQueryBuilder()
            ->select('wp')
            ->from(WorkoutPlan::class, 'wp')
            ->where('wp.user = :user')
            ->andWhere('wp.plannedAt >= :since')
            ->andWhere('wp.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('since', $since)
            ->setParameter('statuses', [
                WorkoutPlanStatus::Completed->value,
                WorkoutPlanStatus::InProgress->value,
            ])
            ->orderBy('wp.plannedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $recentMuscleGroups = [];
        foreach ($recentPlans as $plan) {
            $groups = $plan->getTargetMuscleGroups();
            if (is_array($groups)) {
                $recentMuscleGroups = array_merge($recentMuscleGroups, $groups);
            }
        }

        return array_unique($recentMuscleGroups);
    }

    // ========================================================================
    // Private Helpers
    // ========================================================================

    /**
     * Auto-detect the activity category from user's training preferences.
     *
     * Maps WorkoutType enum to activity category slug. Falls back to 'strength'
     * if no preference is set.
     */
    private function detectActivityCategory(User $user): string
    {
        $workoutType = $user->getWorkoutType();

        if ($workoutType === null) {
            return 'strength';
        }

        return match ($workoutType) {
            \App\Domain\User\Enum\WorkoutType::Cardio => 'running',
            \App\Domain\User\Enum\WorkoutType::Strength => 'strength',
            \App\Domain\User\Enum\WorkoutType::Mixed, \App\Domain\User\Enum\WorkoutType::Crossfit => 'strength',
            \App\Domain\User\Enum\WorkoutType::Gymnastics => 'flexibility',
            \App\Domain\User\Enum\WorkoutType::MartialArts => 'combat',
            \App\Domain\User\Enum\WorkoutType::Yoga => 'yoga',
        };
    }

    /**
     * Get training days per week from user preferences.
     * Maps the TrainingFrequency enum to a numeric value.
     */
    private function getTrainingDaysPerWeek(User $user): int
    {
        $pref = $this->trainingPreferenceRepository->findByUser($user);

        if ($pref === null || $pref->getTrainingFrequency() === null) {
            return 3; // Default to 3 days/week
        }

        return self::FREQUENCY_TO_DAYS[$pref->getTrainingFrequency()->value] ?? 3;
    }

    /**
     * Select the next day in the split cycle that hasn't been trained recently.
     *
     * Compares the split template's day configurations against recently trained
     * muscle groups, and picks the first day whose muscle groups haven't been
     * trained in the last 7 days. Falls back to the first day if all have been done.
     *
     * @return array The day config with keys: day, name, muscleGroups
     */
    private function selectSplitDay(User $user, ?SplitTemplate $template): array
    {
        // Default day config if no template is available
        $defaultDay = [
            'day' => 1,
            'name' => 'Full Body',
            'muscleGroups' => ['chest', 'back', 'quads'],
        ];

        if ($template === null) {
            return $defaultDay;
        }

        $dayConfigs = $template->getDayConfigs();
        if (empty($dayConfigs)) {
            return $defaultDay;
        }

        // Get recently trained muscle groups to find the next untrained day
        $recentMuscleGroups = $this->getRecentWorkoutMuscleGroups($user, 7);

        // Find the first day whose primary muscle groups haven't been trained recently
        foreach ($dayConfigs as $dayConfig) {
            $dayMuscles = $dayConfig['muscleGroups'] ?? [];
            $overlap = array_intersect($dayMuscles, $recentMuscleGroups);

            // If less than half of the day's muscle groups were recently trained, use this day
            if (count($overlap) < count($dayMuscles) / 2) {
                return $dayConfig;
            }
        }

        // All days have been recently trained; cycle back to the first day
        return $dayConfigs[0];
    }

    /**
     * Apply synergy rules to expand the target muscle groups.
     *
     * For example: if training chest, also add triceps exercises.
     * This mirrors real-world training where push muscles are trained together.
     *
     * @param string[] $muscleGroups Primary muscle groups
     * @return string[] Expanded list including synergistic muscle groups
     */
    private function applySynergyRules(array $muscleGroups): array
    {
        $expanded = $muscleGroups;

        foreach ($muscleGroups as $group) {
            if (isset(self::SYNERGY_MAP[$group])) {
                foreach (self::SYNERGY_MAP[$group] as $synergy) {
                    if (!in_array($synergy, $expanded, true)) {
                        $expanded[] = $synergy;
                    }
                }
            }
        }

        return $expanded;
    }

    /**
     * Get exercise slugs from the user's most recent workout targeting the same muscle groups.
     * Used to provide variety by excluding these exercises from the next plan.
     *
     * @param string[] $muscleGroups Target muscle groups
     * @return string[] Exercise slugs to exclude
     */
    private function getRecentExerciseSlugs(User $user, array $muscleGroups): array
    {
        // Find the most recent completed plan targeting overlapping muscle groups
        $recentPlans = $this->entityManager->createQueryBuilder()
            ->select('wp')
            ->from(WorkoutPlan::class, 'wp')
            ->where('wp.user = :user')
            ->andWhere('wp.activityType = :type')
            ->andWhere('wp.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('type', 'strength')
            ->setParameter('statuses', [
                WorkoutPlanStatus::Completed->value,
                WorkoutPlanStatus::InProgress->value,
            ])
            ->orderBy('wp.plannedAt', 'DESC')
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();

        $slugs = [];
        foreach ($recentPlans as $plan) {
            $planMuscles = $plan->getTargetMuscleGroups() ?? [];
            // Check if this plan trained overlapping muscle groups
            if (!empty(array_intersect($planMuscles, $muscleGroups))) {
                foreach ($plan->getExercises() as $planExercise) {
                    $slugs[] = $planExercise->getExercise()->getSlug();
                }
            }
        }

        return array_unique($slugs);
    }

    /**
     * Build reward tiers specifically for strength workouts.
     *
     * Strength rewards are based on exercise completion rather than distance:
     * - Bronze: complete 3 exercises (guaranteed XP for effort)
     * - Silver: complete all exercises (normal XP)
     * - Gold: complete all exercises with an extra set (bonus XP)
     */
    private function getStrengthRewardTiers(int $baseXp, int $exerciseCount): array
    {
        return [
            'bronze' => [
                'threshold' => 'complete_3_exercises',
                'xp' => (int) ($baseXp * 0.5),
            ],
            'silver' => [
                'threshold' => 'complete_all',
                'xp' => $baseXp,
            ],
            'gold' => [
                'threshold' => 'complete_all_with_extra_set',
                'xp' => $baseXp * 2,
            ],
        ];
    }

    /**
     * Attach exercises to a workout plan with default programming from the exercise catalog.
     *
     * @param Exercise[] $exercises Exercises to attach, in order
     */
    private function attachExercisesToPlan(WorkoutPlan $plan, array $exercises): void
    {
        $orderIndex = 1;
        foreach ($exercises as $exercise) {
            $planExercise = new WorkoutPlanExercise();
            $planExercise->setWorkoutPlan($plan);
            $planExercise->setExercise($exercise);
            $planExercise->setOrderIndex($orderIndex);
            $planExercise->setSets($exercise->getDefaultSets());
            $planExercise->setRepsMin($exercise->getDefaultRepsMin());
            $planExercise->setRepsMax($exercise->getDefaultRepsMax());
            $planExercise->setRestSeconds($exercise->getDefaultRestSeconds());

            $plan->addExercise($planExercise);
            $this->entityManager->persist($planExercise);
            $orderIndex++;
        }
    }
}
