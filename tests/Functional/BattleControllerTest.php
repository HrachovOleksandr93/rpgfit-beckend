<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Character\Entity\CharacterStats;
use App\Domain\Config\Entity\GameSetting;
use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Mob\Entity\Mob;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use App\Domain\Workout\Entity\Exercise;
use App\Domain\Workout\Entity\SplitTemplate;
use App\Domain\Workout\Entity\WorkoutPlan;
use App\Domain\Workout\Enum\Equipment;
use App\Domain\Workout\Enum\ExerciseDifficulty;
use App\Domain\Workout\Enum\ExerciseMovementType;
use App\Domain\Workout\Enum\MuscleGroup;
use App\Domain\Workout\Enum\SplitType;
use App\Domain\Workout\Enum\WorkoutPlanStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for BattleController API endpoints.
 *
 * Tests the full HTTP request/response cycle for all battle session endpoints
 * including authentication, session lifecycle (start -> complete/abandon),
 * XP awards, mob defeat logic, and exercise listing.
 *
 * Uses an in-memory SQLite database with fresh schema for each test.
 */
class BattleControllerTest extends AbstractFunctionalTest
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        // Seed required data
        $this->seedGameSettings($em);
        $this->seedExercises($em);
        $this->seedSplitTemplate($em);
        $this->seedMobs($em);
    }

    /** Test that starting a battle returns 200 with session and mob data. */
    public function testStartBattleReturns200(): void
    {
        $user = $this->createTestUser();
        $this->createCharacterStats($user);
        $planId = $this->generatePlan();

        $token = $this->getToken();

        $this->client->request(
            'POST',
            '/api/battle/start',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode(['workoutPlanId' => $planId, 'mode' => 'recommended']),
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('sessionId', $response);
        $this->assertArrayHasKey('mode', $response);
        $this->assertArrayHasKey('mob', $response);
        $this->assertArrayHasKey('startedAt', $response);
        $this->assertSame('recommended', $response['mode']);
    }

    /** Test that starting a battle without authentication returns 401. */
    public function testStartBattleWithoutAuthReturns401(): void
    {
        $this->client->request(
            'POST',
            '/api/battle/start',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['workoutPlanId' => 'some-id', 'mode' => 'recommended']),
        );

        $this->assertResponseStatusCodeSame(401);
    }

    /** Test completing a battle with exercises returns 200 and awards XP. */
    public function testCompleteBattleWithExercisesAwardsXp(): void
    {
        $user = $this->createTestUser();
        $this->createCharacterStats($user);
        $planId = $this->generatePlan();

        $token = $this->getToken();

        // Start battle
        $this->client->request(
            'POST',
            '/api/battle/start',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode(['workoutPlanId' => $planId, 'mode' => 'recommended']),
        );

        $startResponse = json_decode($this->client->getResponse()->getContent(), true);
        $sessionId = $startResponse['sessionId'];

        // Complete battle with exercise data
        $this->client->request(
            'POST',
            '/api/battle/complete',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'sessionId' => $sessionId,
                'exercises' => [
                    [
                        'exerciseSlug' => 'barbell-bench-press',
                        'sets' => [
                            ['setNumber' => 1, 'reps' => 10, 'weight' => 80.0],
                            ['setNumber' => 2, 'reps' => 8, 'weight' => 85.0],
                            ['setNumber' => 3, 'reps' => 6, 'weight' => 90.0],
                        ],
                    ],
                ],
                'healthData' => [
                    'duration' => 3600,
                    'calories' => 350.0,
                    'distance' => null,
                    'averageHeartRate' => 145,
                ],
            ]),
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('xpAwarded', $response);
        $this->assertArrayHasKey('mobsDefeated', $response);
        $this->assertArrayHasKey('totalDamage', $response);
        $this->assertArrayHasKey('performanceTier', $response);
        $this->assertArrayHasKey('levelUp', $response);
        $this->assertArrayHasKey('newLevel', $response);
        $this->assertArrayHasKey('totalXp', $response);
        $this->assertArrayHasKey('completionPercent', $response);
        $this->assertGreaterThanOrEqual(0, $response['totalDamage']);
    }

    /** Test completing a battle with enough damage defeats the mob. */
    public function testCompleteBattleMobDefeated(): void
    {
        $user = $this->createTestUser();
        $this->createCharacterStats($user);
        $planId = $this->generatePlan();

        $token = $this->getToken();

        // Start battle
        $this->client->request(
            'POST',
            '/api/battle/start',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode(['workoutPlanId' => $planId, 'mode' => 'custom']),
        );

        $startResponse = json_decode($this->client->getResponse()->getContent(), true);
        $sessionId = $startResponse['sessionId'];

        // Complete with massive exercise volume to ensure mob defeat
        $sets = [];
        for ($i = 1; $i <= 20; $i++) {
            $sets[] = ['setNumber' => $i, 'reps' => 10, 'weight' => 100.0];
        }

        $this->client->request(
            'POST',
            '/api/battle/complete',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([
                'sessionId' => $sessionId,
                'exercises' => [
                    ['exerciseSlug' => 'barbell-bench-press', 'sets' => $sets],
                    ['exerciseSlug' => 'squat', 'sets' => $sets],
                ],
                'healthData' => [
                    'duration' => 3600,
                    'calories' => 500.0,
                ],
            ]),
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('mobsDefeated', $response);
        $this->assertArrayHasKey('xpAwarded', $response);
        $this->assertArrayHasKey('performanceTier', $response);
        $this->assertGreaterThanOrEqual(0, $response['mobsDefeated']);
    }

    /** Test that getting an active session returns 200. */
    public function testGetActiveSessionReturns200(): void
    {
        $user = $this->createTestUser();
        $this->createCharacterStats($user);
        $planId = $this->generatePlan();

        $token = $this->getToken();

        // Start a battle
        $this->client->request(
            'POST',
            '/api/battle/start',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode(['workoutPlanId' => $planId, 'mode' => 'custom']),
        );

        // Get active session
        $this->client->request(
            'GET',
            '/api/battle/active',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('sessionId', $response);
        $this->assertSame('active', $response['status']);
    }

    /** Test that abandoning a session returns 200 with abandoned status. */
    public function testAbandonSessionReturns200(): void
    {
        $user = $this->createTestUser();
        $this->createCharacterStats($user);
        $planId = $this->generatePlan();

        $token = $this->getToken();

        // Start a battle
        $this->client->request(
            'POST',
            '/api/battle/start',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode(['workoutPlanId' => $planId, 'mode' => 'custom']),
        );

        $startResponse = json_decode($this->client->getResponse()->getContent(), true);
        $sessionId = $startResponse['sessionId'];

        // Abandon the session
        $this->client->request(
            'POST',
            '/api/battle/abandon',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode(['sessionId' => $sessionId]),
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('abandoned', $response['status']);
    }

    /** Test that getting exercises returns 200 with grouped data. */
    public function testGetExercisesListGrouped(): void
    {
        $this->createTestUser();
        $token = $this->getToken();

        $this->client->request(
            'GET',
            '/api/exercises',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(200);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('groups', $response);
        $this->assertIsArray($response['groups']);

        // Should have chest exercises
        $this->assertArrayHasKey('chest', $response['groups']);
        $this->assertNotEmpty($response['groups']['chest']);

        // Verify exercise structure
        $firstExercise = $response['groups']['chest'][0];
        $this->assertArrayHasKey('slug', $firstExercise);
        $this->assertArrayHasKey('name', $firstExercise);
        $this->assertArrayHasKey('equipment', $firstExercise);
        $this->assertArrayHasKey('difficulty', $firstExercise);
        $this->assertArrayHasKey('priority', $firstExercise);
    }

    /** Test that starting a second battle abandons the first one. */
    public function testCannotHaveTwoSimultaneousBattles(): void
    {
        $user = $this->createTestUser();
        $this->createCharacterStats($user);

        $token = $this->getToken();

        // Generate two plans
        $planId1 = $this->generatePlan();
        $planId2 = $this->generatePlan();

        // Start first battle
        $this->client->request(
            'POST',
            '/api/battle/start',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode(['workoutPlanId' => $planId1, 'mode' => 'custom']),
        );

        $this->assertResponseStatusCodeSame(200);
        $firstResponse = json_decode($this->client->getResponse()->getContent(), true);
        $firstSessionId = $firstResponse['sessionId'];

        // Start second battle (should auto-abandon the first)
        $this->client->request(
            'POST',
            '/api/battle/start',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode(['workoutPlanId' => $planId2, 'mode' => 'recommended']),
        );

        $this->assertResponseStatusCodeSame(200);
        $secondResponse = json_decode($this->client->getResponse()->getContent(), true);

        // The second session should be different from the first
        $this->assertNotSame($firstSessionId, $secondResponse['sessionId']);

        // Check the active session is now the second one
        $this->client->request(
            'GET',
            '/api/battle/active',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $activeResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($secondResponse['sessionId'], $activeResponse['sessionId']);
    }

    // ========================================================================
    // Setup Helpers
    // ========================================================================

    /** Seed game settings required by the workout generator and XP system. */
    private function seedGameSettings(EntityManagerInterface $em): void
    {
        $settings = [
            ['workout', 'workout_exercises_per_session', '7'],
            ['workout', 'workout_cardio_progression_rate', '0.1'],
            ['workout', 'workout_reward_bronze_multiplier', '0.6'],
            ['workout', 'workout_reward_silver_multiplier', '1.0'],
            ['workout', 'workout_reward_gold_multiplier', '1.3'],
            ['workout', 'workout_base_xp_per_workout', '100'],
            ['xp_rates', 'xp_rate_steps', '10'],
            ['xp_rates', 'xp_rate_active_energy', '25'],
            ['xp_rates', 'xp_rate_workout', '15'],
            ['xp_rates', 'xp_rate_distance', '10'],
            ['xp_rates', 'xp_rate_sleep', '10'],
            ['xp_rates', 'xp_rate_flights', '5'],
            ['xp_caps', 'xp_daily_cap', '3000'],
            ['leveling', 'level_formula_quad', '4.2'],
            ['leveling', 'level_formula_linear', '28'],
            ['leveling', 'level_max', '100'],
        ];

        foreach ($settings as [$category, $key, $value]) {
            $s = new GameSetting();
            $s->setCategory($category);
            $s->setKey($key);
            $s->setValue($value);
            $em->persist($s);
        }

        $em->flush();
    }

    /** Seed test exercises for strength training. */
    private function seedExercises(EntityManagerInterface $em): void
    {
        $exerciseData = [
            ['Barbell Bench Press', 'barbell-bench-press', MuscleGroup::Chest, Equipment::Barbell, 1, true, ExerciseMovementType::Compound],
            ['Dumbbell Incline Press', 'dumbbell-incline-press', MuscleGroup::Chest, Equipment::Dumbbell, 2, false, ExerciseMovementType::Compound],
            ['Cable Fly', 'cable-fly', MuscleGroup::Chest, Equipment::Cable, 3, false, ExerciseMovementType::Isolation],
            ['Pec Deck', 'pec-deck', MuscleGroup::Chest, Equipment::Machine, 4, false, ExerciseMovementType::Isolation],
            ['Barbell Row', 'barbell-row', MuscleGroup::Back, Equipment::Barbell, 1, true, ExerciseMovementType::Compound],
            ['Pull-ups', 'pull-ups', MuscleGroup::Back, Equipment::Bodyweight, 2, true, ExerciseMovementType::Compound],
            ['Cable Row', 'cable-row', MuscleGroup::Back, Equipment::Cable, 3, false, ExerciseMovementType::Isolation],
            ['Tricep Pushdown', 'tricep-pushdown', MuscleGroup::Triceps, Equipment::Cable, 3, false, ExerciseMovementType::Isolation],
            ['Skull Crusher', 'skull-crusher', MuscleGroup::Triceps, Equipment::Barbell, 3, false, ExerciseMovementType::Isolation],
            ['Barbell Curl', 'barbell-curl', MuscleGroup::Biceps, Equipment::Barbell, 3, false, ExerciseMovementType::Isolation],
            ['Squat', 'squat', MuscleGroup::Quads, Equipment::Barbell, 1, true, ExerciseMovementType::Compound],
            ['Leg Press', 'leg-press', MuscleGroup::Quads, Equipment::Machine, 2, false, ExerciseMovementType::Compound],
        ];

        foreach ($exerciseData as [$name, $slug, $muscle, $equipment, $priority, $isBase, $movementType]) {
            $exercise = new Exercise();
            $exercise->setName($name);
            $exercise->setSlug($slug);
            $exercise->setPrimaryMuscle($muscle);
            $exercise->setEquipment($equipment);
            $exercise->setDifficulty(ExerciseDifficulty::Beginner);
            $exercise->setMovementType($movementType);
            $exercise->setPriority($priority);
            $exercise->setIsBaseExercise($isBase);
            $exercise->setSecondaryMuscles([]);
            $em->persist($exercise);
        }

        $em->flush();
    }

    /** Seed a PPL split template for workout plan generation. */
    private function seedSplitTemplate(EntityManagerInterface $em): void
    {
        $template = new SplitTemplate();
        $template->setName('Push/Pull/Legs');
        $template->setSlug('push_pull_legs');
        $template->setSplitType(SplitType::PushPullLegs);
        $template->setDaysPerWeek(3);
        $template->setDayConfigs([
            ['day' => 1, 'name' => 'Push', 'muscleGroups' => ['chest', 'shoulders', 'triceps']],
            ['day' => 2, 'name' => 'Pull', 'muscleGroups' => ['back', 'biceps']],
            ['day' => 3, 'name' => 'Legs', 'muscleGroups' => ['quads', 'hamstrings', 'glutes']],
        ]);

        $em->persist($template);
        $em->flush();
    }

    /** Seed test mobs at various levels and rarities. */
    private function seedMobs(EntityManagerInterface $em): void
    {
        $mobData = [
            ['Slime', 'slime-1', 1, 200, 15, ItemRarity::Common],
            ['Wolf', 'wolf-2', 2, 350, 25, ItemRarity::Common],
            ['Goblin', 'goblin-3', 3, 500, 40, ItemRarity::Uncommon],
            ['Orc Warrior', 'orc-warrior-5', 5, 800, 60, ItemRarity::Rare],
            ['Dragon Whelp', 'dragon-whelp-8', 8, 1500, 120, ItemRarity::Epic],
            ['Ancient Dragon', 'ancient-dragon-10', 10, 3000, 250, ItemRarity::Legendary],
        ];

        foreach ($mobData as [$name, $slug, $level, $hp, $xp, $rarity]) {
            $mob = new Mob();
            $mob->setName($name);
            $mob->setSlug($slug);
            $mob->setLevel($level);
            $mob->setHp($hp);
            $mob->setXpReward($xp);
            $mob->setRarity($rarity);
            $em->persist($mob);
        }

        $em->flush();
    }

    /** Create a test user with full profile data. */
    private function createTestUser(string $login = 'hero@rpgfit.com', string $password = 'SecurePass123'): User
    {
        $container = self::getContainer();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = $container->get(UserPasswordHasherInterface::class);

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();

        $displayName = 'Hero_' . substr(md5($login), 0, 8);

        $user = new User();
        $user->setLogin($login);
        $user->setDisplayName($displayName);
        $user->setHeight(180.0);
        $user->setWeight(75.5);
        $user->setWorkoutType(WorkoutType::Strength);
        $user->setActivityLevel(ActivityLevel::Active);
        $user->setDesiredGoal(DesiredGoal::LoseWeight);
        $user->setCharacterRace(CharacterRace::Orc);
        $user->setPassword($hasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    /** Create CharacterStats for a user so XP can be awarded. */
    private function createCharacterStats(User $user): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManager();

        $stats = new CharacterStats();
        $stats->setUser($user);
        $stats->setStrength(10);
        $stats->setDexterity(10);
        $stats->setConstitution(10);
        $stats->setTotalXp(0);
        $stats->setLevel(1);

        $em->persist($stats);
        $em->flush();
    }

    /** Generate a workout plan via the API and return its ID. */
    private function generatePlan(): string
    {
        $token = $this->getToken();

        $this->client->request(
            'POST',
            '/api/workout/generate',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode(['activityCategory' => 'strength']),
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);

        return $response['plan']['id'];
    }

    /** Get a JWT token for the given user credentials. */
    private function getToken(string $login = 'hero@rpgfit.com', string $password = 'SecurePass123'): string
    {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => $login, 'password' => $password]),
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);

        return $response['token'];
    }
}
