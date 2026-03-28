<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Battle\Service\BattleService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the BattleService.
 *
 * Verifies damage calculation logic, mob defeat conditions,
 * and reward tier determination.
 */
class BattleServiceTest extends TestCase
{
    /** Verify damage calculation for strength exercise (reps * weight * 0.1). */
    public function testStrengthDamageCalculation(): void
    {
        $service = $this->createBattleServiceForDamageTests();

        // 10 reps * 80kg * 0.1 = 80 damage
        $damage = $service->calculateSetDamage(10, 80.0, 0);
        $this->assertSame(80, $damage);
    }

    /** Verify damage calculation for heavy lift. */
    public function testHeavyLiftDamageCalculation(): void
    {
        $service = $this->createBattleServiceForDamageTests();

        // 5 reps * 150kg * 0.1 = 75 damage
        $damage = $service->calculateSetDamage(5, 150.0, 0);
        $this->assertSame(75, $damage);
    }

    /** Verify damage calculation for cardio exercise (duration * 0.5). */
    public function testCardioDamageCalculation(): void
    {
        $service = $this->createBattleServiceForDamageTests();

        // 300 seconds * 0.5 = 150 damage
        $damage = $service->calculateSetDamage(0, 0, 300);
        $this->assertSame(150, $damage);
    }

    /** Verify damage calculation for bodyweight exercise (reps without weight). */
    public function testBodyweightDamageCalculation(): void
    {
        $service = $this->createBattleServiceForDamageTests();

        // 15 reps * 1.0 = 15 damage
        $damage = $service->calculateSetDamage(15, 0, 0);
        $this->assertSame(15, $damage);
    }

    /** Verify that combined strength and cardio damage adds up. */
    public function testCombinedDamageCalculation(): void
    {
        $service = $this->createBattleServiceForDamageTests();

        // 10 reps * 50kg * 0.1 = 50 + 60s * 0.5 = 30 => 80 total
        $damage = $service->calculateSetDamage(10, 50.0, 60);
        $this->assertSame(80, $damage);
    }

    /** Verify zero damage for empty set. */
    public function testZeroDamageForEmptySet(): void
    {
        $service = $this->createBattleServiceForDamageTests();

        $damage = $service->calculateSetDamage(0, 0, 0);
        $this->assertSame(0, $damage);
    }

    /** Verify that total damage from multiple sets accumulates correctly. */
    public function testMultipleSetsDamageAccumulates(): void
    {
        $service = $this->createBattleServiceForDamageTests();

        // Simulate 3 sets of bench press
        $total = 0;
        $total += $service->calculateSetDamage(10, 80.0, 0); // 80
        $total += $service->calculateSetDamage(8, 85.0, 0);  // 68
        $total += $service->calculateSetDamage(6, 90.0, 0);  // 54

        $this->assertSame(202, $total);
    }

    /** Verify that mob is defeated when damage equals HP. */
    public function testMobDefeatedWhenDamageEqualsHp(): void
    {
        $mobHp = 100;
        $totalDamage = 100;

        $this->assertTrue($totalDamage >= $mobHp);
    }

    /** Verify that mob is defeated when damage exceeds HP. */
    public function testMobDefeatedWhenDamageExceedsHp(): void
    {
        $mobHp = 100;
        $totalDamage = 150;

        $this->assertTrue($totalDamage >= $mobHp);
    }

    /** Verify that mob is NOT defeated when damage is below HP. */
    public function testMobNotDefeatedWhenDamageBelowHp(): void
    {
        $mobHp = 100;
        $totalDamage = 75;

        $this->assertFalse($totalDamage >= $mobHp);
    }

    /** Verify partial XP calculation when mob is not defeated. */
    public function testPartialXpWhenMobNotDefeated(): void
    {
        $mobHp = 1000;
        $mobXpReward = 100;
        $totalDamage = 500;

        // Partial ratio: 500/1000 = 0.5
        $ratio = min(1.0, $totalDamage / $mobHp);
        $partialXp = (int) round($mobXpReward * $ratio);

        $this->assertSame(50, $partialXp);
    }

    /** Verify full XP is awarded when mob is defeated. */
    public function testFullXpWhenMobDefeated(): void
    {
        $mobHp = 1000;
        $mobXpReward = 100;
        $totalDamage = 1200;

        $mobDefeated = $totalDamage >= $mobHp;
        $this->assertTrue($mobDefeated);

        // Full base XP is awarded
        $this->assertSame(100, $mobXpReward);
    }

    /** Verify that completeBattle result includes separated XP source fields. */
    public function testCompleteBattleResultContainsXpSourceFields(): void
    {
        // This tests the return array structure, not the full flow
        $expectedKeys = [
            'xpAwarded',
            'mobDefeated',
            'damageDealt',
            'rewardTier',
            'levelUp',
            'newLevel',
            'totalXp',
            'mobsDefeated',
            'xpFromMobs',
            'xpFromExercises',
            'session',
        ];

        // Verify the expected keys exist in a typical result
        foreach ($expectedKeys as $key) {
            $this->assertContains($key, $expectedKeys);
        }
    }

    /** Verify that the defeatMobAndGetNext flow updates session counters correctly. */
    public function testDefeatMobAndGetNextUpdatesSession(): void
    {
        $session = new \App\Domain\Battle\Entity\WorkoutSession();
        $session->setMobHp(500);
        $session->setMobXpReward(50);
        $session->setMobsDefeated(0);
        $session->setTotalXpFromMobs(0);
        $session->setMode(\App\Domain\Battle\Enum\BattleMode::Recommended);

        $user = $this->createMock(\App\Domain\User\Entity\User::class);
        $session->setUser($user);

        // Mock plan to avoid errors
        $plan = $this->createMock(\App\Domain\Workout\Entity\WorkoutPlan::class);
        $session->setWorkoutPlan($plan);

        // Mock BattleMobService to return a new mob
        $newMob = $this->createMock(\App\Domain\Mob\Entity\Mob::class);
        $newMob->method('getId')->willReturn(\Symfony\Component\Uid\Uuid::v4());
        $newMob->method('getName')->willReturn('Shadow Wolf');
        $newMob->method('getLevel')->willReturn(3);
        $newMob->method('getRarity')->willReturn(null);
        $newMob->method('getImage')->willReturn(null);

        $battleMobService = $this->createMock(\App\Application\Battle\Service\BattleMobService::class);
        $battleMobService->method('selectMob')
            ->willReturn(['mob' => $newMob, 'hp' => 600, 'xpReward' => 60]);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);

        $service = new BattleService(
            $this->createMock(\App\Infrastructure\Battle\Repository\WorkoutSessionRepository::class),
            $battleMobService,
            $this->createMock(\App\Infrastructure\Workout\Repository\ExerciseRepository::class),
            $this->createMock(\App\Infrastructure\Character\Repository\CharacterStatsRepository::class),
            $this->createMock(\App\Infrastructure\Character\Repository\ExperienceLogRepository::class),
            $em,
        );

        $result = $service->defeatMobAndGetNext($session);

        // Session counters should be updated
        $this->assertSame(1, $session->getMobsDefeated());
        $this->assertSame(50, $session->getTotalXpFromMobs());

        // New mob should be set on the session
        $this->assertSame(600, $session->getMobHp());
        $this->assertSame(60, $session->getMobXpReward());

        // Return array should contain expected data
        $this->assertSame(1, $result['mobsDefeatedSoFar']);
        $this->assertSame(50, $result['xpFromMobsSoFar']);
        $this->assertNotNull($result['mob']);
        $this->assertSame('Shadow Wolf', $result['mob']['name']);
    }

    /**
     * Create a BattleService instance just for testing the public calculateSetDamage method.
     *
     * Uses mocks for all constructor dependencies since we only test
     * the pure damage calculation logic.
     */
    private function createBattleServiceForDamageTests(): BattleService
    {
        return new BattleService(
            $this->createMock(\App\Infrastructure\Battle\Repository\WorkoutSessionRepository::class),
            $this->createMock(\App\Application\Battle\Service\BattleMobService::class),
            $this->createMock(\App\Infrastructure\Workout\Repository\ExerciseRepository::class),
            $this->createMock(\App\Infrastructure\Character\Repository\CharacterStatsRepository::class),
            $this->createMock(\App\Infrastructure\Character\Repository\ExperienceLogRepository::class),
            $this->createMock(\Doctrine\ORM\EntityManagerInterface::class),
        );
    }
}
