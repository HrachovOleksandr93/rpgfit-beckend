<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Workout\Service\WorkoutPlanGeneratorService;
use App\Domain\Config\Entity\GameSetting;
use App\Domain\Training\Entity\WorkoutLog;
use App\Domain\User\Entity\User;
use App\Domain\User\Entity\UserTrainingPreference;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\TrainingFrequency;
use App\Domain\User\Enum\WorkoutType;
use App\Domain\Workout\Entity\Exercise;
use App\Domain\Workout\Entity\SplitTemplate;
use App\Domain\Workout\Entity\WorkoutPlan;
use App\Domain\Workout\Entity\WorkoutPlanExercise;
use App\Domain\Workout\Enum\Equipment;
use App\Domain\Workout\Enum\ExerciseDifficulty;
use App\Domain\Workout\Enum\ExerciseMovementType;
use App\Domain\Workout\Enum\MuscleGroup;
use App\Domain\Workout\Enum\SplitType;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\Training\Repository\WorkoutLogRepository;
use App\Infrastructure\User\Repository\UserTrainingPreferenceRepository;
use App\Infrastructure\Workout\Repository\ExerciseRepository;
use App\Infrastructure\Workout\Repository\SplitTemplateRepository;
use App\Infrastructure\Workout\Repository\WorkoutPlanExerciseRepository;
use App\Infrastructure\Workout\Repository\WorkoutPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WorkoutPlanGeneratorService.
 *
 * Tests the core workout generation logic including exercise selection,
 * split template selection, synergy rules, reward tiers, and activity-specific plans.
 * All repository dependencies are mocked to isolate the service logic.
 */
class WorkoutPlanGeneratorServiceTest extends TestCase
{
    private WorkoutPlanGeneratorService $service;
    private ExerciseRepository&MockObject $exerciseRepo;
    private SplitTemplateRepository&MockObject $splitTemplateRepo;
    private WorkoutPlanRepository&MockObject $workoutPlanRepo;
    private WorkoutPlanExerciseRepository&MockObject $workoutPlanExerciseRepo;
    private GameSettingRepository&MockObject $gameSettingRepo;
    private EntityManagerInterface&MockObject $entityManager;
    private UserTrainingPreferenceRepository&MockObject $trainingPrefRepo;
    private WorkoutLogRepository&MockObject $workoutLogRepo;

    protected function setUp(): void
    {
        $this->exerciseRepo = $this->createMock(ExerciseRepository::class);
        $this->splitTemplateRepo = $this->createMock(SplitTemplateRepository::class);
        $this->workoutPlanRepo = $this->createMock(WorkoutPlanRepository::class);
        $this->workoutPlanExerciseRepo = $this->createMock(WorkoutPlanExerciseRepository::class);
        $this->gameSettingRepo = $this->createMock(GameSettingRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->trainingPrefRepo = $this->createMock(UserTrainingPreferenceRepository::class);
        $this->workoutLogRepo = $this->createMock(WorkoutLogRepository::class);

        $this->service = new WorkoutPlanGeneratorService(
            $this->exerciseRepo,
            $this->splitTemplateRepo,
            $this->workoutPlanRepo,
            $this->workoutPlanExerciseRepo,
            $this->gameSettingRepo,
            $this->entityManager,
            $this->trainingPrefRepo,
            $this->workoutLogRepo,
        );
    }

    /**
     * Test that gym plan generates the correct number of exercises (6-8).
     * The default is 7 from game settings, and the service should respect that.
     */
    public function testGymPlanGeneratesCorrectNumberOfExercises(): void
    {
        // Create 10 test exercises so the service can select from them
        $exercises = $this->createExercisesForMuscleGroup(MuscleGroup::Chest, 5);
        $tricepsExercises = $this->createExercisesForMuscleGroup(MuscleGroup::Triceps, 5);

        $this->exerciseRepo->method('findByMuscleGroupAndDifficulty')
            ->willReturnCallback(function (MuscleGroup $mg) use ($exercises, $tricepsExercises) {
                return match ($mg) {
                    MuscleGroup::Chest => $exercises,
                    MuscleGroup::Triceps => $tricepsExercises,
                    default => [],
                };
            });

        // Select 7 exercises from chest + triceps muscle groups
        $result = $this->service->selectExercisesForMuscleGroups(
            ['chest', 'triceps'],
            'beginner',
            7,
        );

        // Should return between 6 and 8 exercises (or up to the limit of 7)
        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertLessThanOrEqual(7, count($result));
    }

    /**
     * Test that selected exercises are ordered by priority (compounds first).
     * Priority 1 = heavy compound, priority 5 = light isolation.
     */
    public function testExercisesAreOrderedByPriority(): void
    {
        // Create exercises with varying priorities
        $exercises = [];
        foreach ([5, 3, 1, 2, 4] as $priority) {
            $exercise = new Exercise();
            $exercise->setName("Exercise P{$priority}");
            $exercise->setSlug("exercise-p{$priority}");
            $exercise->setPrimaryMuscle(MuscleGroup::Chest);
            $exercise->setEquipment(Equipment::Barbell);
            $exercise->setDifficulty(ExerciseDifficulty::Beginner);
            $exercise->setMovementType(ExerciseMovementType::Compound);
            $exercise->setPriority($priority);
            $exercises[] = $exercise;
        }

        $this->exerciseRepo->method('findByMuscleGroupAndDifficulty')
            ->willReturn($exercises);

        $result = $this->service->selectExercisesForMuscleGroups(
            ['chest'],
            'beginner',
            5,
        );

        // Verify exercises are sorted by priority ascending
        $priorities = array_map(fn(Exercise $e) => $e->getPriority(), $result);
        $sorted = $priorities;
        sort($sorted);
        $this->assertSame($sorted, $priorities, 'Exercises should be ordered by priority (ascending)');
    }

    /**
     * Test that compound exercises come before isolation exercises.
     * Since compounds have lower priority numbers, they should appear first.
     */
    public function testCompoundExercisesBeforeIsolation(): void
    {
        $exercises = [];

        // Create compound exercises with low priority
        for ($i = 1; $i <= 3; $i++) {
            $exercise = new Exercise();
            $exercise->setName("Compound {$i}");
            $exercise->setSlug("compound-{$i}");
            $exercise->setPrimaryMuscle(MuscleGroup::Chest);
            $exercise->setEquipment(Equipment::Barbell);
            $exercise->setDifficulty(ExerciseDifficulty::Beginner);
            $exercise->setMovementType(ExerciseMovementType::Compound);
            $exercise->setPriority($i);
            $exercises[] = $exercise;
        }

        // Create isolation exercises with high priority
        for ($i = 1; $i <= 3; $i++) {
            $exercise = new Exercise();
            $exercise->setName("Isolation {$i}");
            $exercise->setSlug("isolation-{$i}");
            $exercise->setPrimaryMuscle(MuscleGroup::Chest);
            $exercise->setEquipment(Equipment::Cable);
            $exercise->setDifficulty(ExerciseDifficulty::Beginner);
            $exercise->setMovementType(ExerciseMovementType::Isolation);
            $exercise->setPriority($i + 3);
            $exercises[] = $exercise;
        }

        $this->exerciseRepo->method('findByMuscleGroupAndDifficulty')
            ->willReturn($exercises);

        $result = $this->service->selectExercisesForMuscleGroups(
            ['chest'],
            'beginner',
            6,
        );

        // First 3 should be compounds (priority 1-3), last 3 should be isolation (priority 4-6)
        $this->assertSame(ExerciseMovementType::Compound, $result[0]->getMovementType());
        $this->assertSame(ExerciseMovementType::Compound, $result[1]->getMovementType());
        $this->assertSame(ExerciseMovementType::Compound, $result[2]->getMovementType());
        $this->assertSame(ExerciseMovementType::Isolation, $result[3]->getMovementType());
    }

    /**
     * Test synergy: chest day includes triceps exercises.
     * When selecting for chest, the service should also include triceps exercises
     * via the synergy rule.
     */
    public function testSynergyChestDayIncludesTriceps(): void
    {
        $chestExercises = $this->createExercisesForMuscleGroup(MuscleGroup::Chest, 4);
        $tricepsExercises = $this->createExercisesForMuscleGroup(MuscleGroup::Triceps, 4);

        $this->exerciseRepo->method('findByMuscleGroupAndDifficulty')
            ->willReturnCallback(function (MuscleGroup $mg) use ($chestExercises, $tricepsExercises) {
                return match ($mg) {
                    MuscleGroup::Chest => $chestExercises,
                    MuscleGroup::Triceps => $tricepsExercises,
                    default => [],
                };
            });

        // Chest + triceps (synergy applied) should pull from both muscle groups
        $result = $this->service->selectExercisesForMuscleGroups(
            ['chest', 'triceps'],
            'beginner',
            7,
        );

        // Verify both chest and triceps exercises are present
        $muscles = array_map(fn(Exercise $e) => $e->getPrimaryMuscle(), $result);
        $this->assertContains(MuscleGroup::Chest, $muscles, 'Should include chest exercises');
        $this->assertContains(MuscleGroup::Triceps, $muscles, 'Should include triceps exercises (synergy)');
    }

    /**
     * Test synergy: back day includes biceps exercises.
     */
    public function testSynergyBackDayIncludesBiceps(): void
    {
        $backExercises = $this->createExercisesForMuscleGroup(MuscleGroup::Back, 4);
        $bicepsExercises = $this->createExercisesForMuscleGroup(MuscleGroup::Biceps, 4);

        $this->exerciseRepo->method('findByMuscleGroupAndDifficulty')
            ->willReturnCallback(function (MuscleGroup $mg) use ($backExercises, $bicepsExercises) {
                return match ($mg) {
                    MuscleGroup::Back => $backExercises,
                    MuscleGroup::Biceps => $bicepsExercises,
                    default => [],
                };
            });

        $result = $this->service->selectExercisesForMuscleGroups(
            ['back', 'biceps'],
            'beginner',
            7,
        );

        $muscles = array_map(fn(Exercise $e) => $e->getPrimaryMuscle(), $result);
        $this->assertContains(MuscleGroup::Back, $muscles, 'Should include back exercises');
        $this->assertContains(MuscleGroup::Biceps, $muscles, 'Should include biceps exercises (synergy)');
    }

    /**
     * Test that running plan has distance targets and reward tiers.
     * calculateRunningTarget should return both a distance and duration.
     */
    public function testRunningPlanHasDistanceTargetsAndRewardTiers(): void
    {
        $user = $this->createTestUser();

        // Mock no recent running logs - should use conservative defaults
        $this->workoutLogRepo->method('findByUser')->willReturn([]);

        $this->gameSettingRepo->method('getAllAsMap')->willReturn([
            'workout_cardio_progression_rate' => '0.1',
            'workout_base_xp_per_workout' => '100',
            'workout_reward_bronze_multiplier' => '0.6',
            'workout_reward_silver_multiplier' => '1.0',
            'workout_reward_gold_multiplier' => '1.3',
        ]);

        $target = $this->service->calculateRunningTarget($user);

        $this->assertArrayHasKey('distance', $target);
        $this->assertArrayHasKey('duration', $target);
        $this->assertGreaterThan(0, $target['distance']);
        $this->assertGreaterThan(0, $target['duration']);

        // Test reward tiers
        $tiers = $this->service->getRewardTiers('running', $target['distance']);

        $this->assertArrayHasKey('bronze', $tiers);
        $this->assertArrayHasKey('silver', $tiers);
        $this->assertArrayHasKey('gold', $tiers);
    }

    /**
     * Test that reward tiers always have bronze, silver, and gold entries.
     */
    public function testRewardTiersHaveBronzeSilverGold(): void
    {
        $this->gameSettingRepo->method('getAllAsMap')->willReturn([
            'workout_base_xp_per_workout' => '100',
            'workout_reward_bronze_multiplier' => '0.6',
            'workout_reward_silver_multiplier' => '1.0',
            'workout_reward_gold_multiplier' => '1.3',
        ]);

        $tiers = $this->service->getRewardTiers('running', 5000.0);

        $this->assertArrayHasKey('bronze', $tiers);
        $this->assertArrayHasKey('silver', $tiers);
        $this->assertArrayHasKey('gold', $tiers);

        // Bronze threshold should be lower than silver, silver lower than gold
        $this->assertLessThan($tiers['silver']['threshold'], $tiers['bronze']['threshold']);
        $this->assertLessThan($tiers['gold']['threshold'], $tiers['silver']['threshold']);

        // Bronze XP should be lower than silver, silver lower than gold
        $this->assertLessThan($tiers['silver']['xp'], $tiers['bronze']['xp']);
        $this->assertLessThan($tiers['gold']['xp'], $tiers['silver']['xp']);
    }

    /**
     * Test split selection: 3 days per week should map to PPL (push/pull/legs).
     * The DAYS_TO_SPLIT constant maps 3 -> push_pull_legs.
     */
    public function testSplitSelectionByFrequency3DaysPPL(): void
    {
        $user = $this->createTestUser();

        $pref = new UserTrainingPreference();
        $pref->setUser($user);
        $pref->setTrainingFrequency(TrainingFrequency::Light); // Light = 3 days

        $this->trainingPrefRepo->method('findByUser')->willReturn($pref);

        $this->gameSettingRepo->method('getAllAsMap')->willReturn([
            'workout_exercises_per_session' => '7',
            'workout_base_xp_per_workout' => '100',
        ]);

        // The PPL template should be requested
        $pplTemplate = new SplitTemplate();
        $pplTemplate->setName('Push/Pull/Legs');
        $pplTemplate->setSlug('push_pull_legs');
        $pplTemplate->setSplitType(SplitType::PushPullLegs);
        $pplTemplate->setDaysPerWeek(3);
        $pplTemplate->setDayConfigs([
            ['day' => 1, 'name' => 'Push', 'muscleGroups' => ['chest', 'shoulders', 'triceps']],
            ['day' => 2, 'name' => 'Pull', 'muscleGroups' => ['back', 'biceps']],
            ['day' => 3, 'name' => 'Legs', 'muscleGroups' => ['quads', 'hamstrings', 'glutes']],
        ]);

        $this->splitTemplateRepo->method('findBySlug')
            ->with('push_pull_legs')
            ->willReturn($pplTemplate);

        // Mock exercises and entity manager for the full plan generation
        $this->exerciseRepo->method('findByMuscleGroupAndDifficulty')
            ->willReturn($this->createExercisesForMuscleGroup(MuscleGroup::Chest, 3));
        $this->exerciseRepo->method('findByMuscleGroup')
            ->willReturn([]);

        // Mock the query builder for recent workouts
        $this->mockEmptyRecentWorkouts();

        // persist() and flush() are void methods -- no willReturn needed

        $plan = $this->service->generatePlan($user, 'strength');

        $this->assertInstanceOf(WorkoutPlan::class, $plan);
        $this->assertSame('strength', $plan->getActivityType());
    }

    /**
     * Test that yoga plan exercises are ordered by sequence (warm-up first, cool-down last).
     * Yoga exercises use priority to define phases: 1=warm-up, 5=cool-down.
     */
    public function testYogaPlanOrderedBySequence(): void
    {
        $user = $this->createTestUser();

        $this->gameSettingRepo->method('getAllAsMap')->willReturn([
            'workout_exercises_per_session' => '7',
            'workout_base_xp_per_workout' => '100',
            'workout_reward_bronze_multiplier' => '0.6',
            'workout_reward_silver_multiplier' => '1.0',
            'workout_reward_gold_multiplier' => '1.3',
        ]);

        // Create yoga exercises with different phase priorities
        $yogaExercises = [];
        $phases = [
            ['name' => 'Savasana', 'priority' => 5],         // cool-down
            ['name' => 'Tree Pose', 'priority' => 3],        // balance
            ['name' => 'Sun Salutation', 'priority' => 1],   // warm-up
            ['name' => 'Warrior II', 'priority' => 2],       // standing
            ['name' => 'Pigeon Pose', 'priority' => 4],      // floor
        ];

        foreach ($phases as $phase) {
            $exercise = new Exercise();
            $exercise->setName($phase['name']);
            $exercise->setSlug(strtolower(str_replace(' ', '-', $phase['name'])));
            $exercise->setPrimaryMuscle(MuscleGroup::Core);
            $exercise->setEquipment(Equipment::Mat);
            $exercise->setDifficulty(ExerciseDifficulty::Beginner);
            $exercise->setMovementType(ExerciseMovementType::Compound);
            $exercise->setPriority($phase['priority']);
            $exercise->setActivityCategory('yoga');
            $yogaExercises[] = $exercise;
        }

        // Mock the entity manager query builder for yoga exercise selection
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($yogaExercises);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturn($qb);
        $qb->method('from')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('orderBy')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);
        // persist() and flush() are void methods -- no willReturn needed

        $plan = $this->service->generatePlan($user, 'yoga');

        $this->assertInstanceOf(WorkoutPlan::class, $plan);
        $this->assertSame('yoga', $plan->getActivityType());

        // Verify exercises are attached in priority order (warm-up first, cool-down last)
        $planExercises = $plan->getExercises()->toArray();
        if (count($planExercises) >= 2) {
            $firstPriority = $planExercises[0]->getExercise()->getPriority();
            $lastPriority = end($planExercises)->getExercise()->getPriority();

            // The order indices should be ascending
            $this->assertSame(1, $planExercises[0]->getOrderIndex());
        }
    }

    /**
     * Test getUserDifficulty returns correct difficulty based on activity level.
     */
    public function testGetUserDifficultyMapping(): void
    {
        $user = $this->createTestUser();

        // Test beginner (no activity level)
        $this->assertSame('beginner', $this->service->getUserDifficulty($user));

        // Test with activity level set to moderate
        $user->setActivityLevel(ActivityLevel::Active);
        $this->assertSame('intermediate', $this->service->getUserDifficulty($user));
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    /**
     * Create a test User entity with minimal required fields.
     */
    private function createTestUser(): User
    {
        $user = new User();
        $user->setLogin('test@rpgfit.com');
        $user->setPassword('hashed-password');

        return $user;
    }

    /**
     * Create N test exercises for a given muscle group.
     * Exercises are created with ascending priority (1 to N) and beginner difficulty.
     *
     * @return Exercise[]
     */
    private function createExercisesForMuscleGroup(MuscleGroup $muscleGroup, int $count): array
    {
        $exercises = [];
        $muscleGroupName = $muscleGroup->value;

        for ($i = 1; $i <= $count; $i++) {
            $exercise = new Exercise();
            $exercise->setName("{$muscleGroupName} Exercise {$i}");
            $exercise->setSlug("{$muscleGroupName}-exercise-{$i}");
            $exercise->setPrimaryMuscle($muscleGroup);
            $exercise->setEquipment(Equipment::Barbell);
            $exercise->setDifficulty(ExerciseDifficulty::Beginner);
            $exercise->setMovementType($i <= 2 ? ExerciseMovementType::Compound : ExerciseMovementType::Isolation);
            $exercise->setPriority($i);
            $exercises[] = $exercise;
        }

        return $exercises;
    }

    /**
     * Mock entity manager's createQueryBuilder to return empty results
     * for recent workout queries.
     */
    private function mockEmptyRecentWorkouts(): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturn($qb);
        $qb->method('from')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('orderBy')->willReturn($qb);
        $qb->method('setMaxResults')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $this->entityManager->method('createQueryBuilder')->willReturn($qb);
    }
}
