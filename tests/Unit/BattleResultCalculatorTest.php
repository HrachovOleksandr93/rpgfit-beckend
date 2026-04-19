<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Battle\Service\BattleResultCalculator;
use App\Domain\Battle\Entity\WorkoutSession;
use App\Domain\Battle\Enum\BattleMode;
use App\Domain\Character\Entity\CharacterStats;
use App\Domain\Character\Enum\StatType;
use App\Domain\Inventory\Entity\ItemCatalog;
use App\Domain\Inventory\Entity\ItemStatBonus;
use App\Domain\Inventory\Entity\UserInventory;
use App\Domain\Skill\Entity\Skill;
use App\Domain\Skill\Entity\SkillStatBonus;
use App\Domain\Skill\Entity\UserSkill;
use App\Domain\User\Entity\User;
use App\Domain\Workout\Entity\Exercise;
use App\Domain\Workout\Entity\WorkoutPlan;
use App\Domain\Workout\Entity\WorkoutPlanExercise;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\Inventory\Repository\ItemCatalogRepository;
use App\Infrastructure\Inventory\Repository\UserInventoryRepository;
use App\Infrastructure\Mob\Repository\MobRepository;
use App\Infrastructure\Skill\Repository\SkillRepository;
use App\Infrastructure\Skill\Repository\UserSkillRepository;
use App\Infrastructure\Workout\Repository\ExerciseRepository;
use App\Application\Character\Service\LevelingService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the BattleResultCalculator service.
 *
 * Verifies all steps of the battle result calculation pipeline:
 * effective stats, volume, damage, mob counts, completion, and performance tiers.
 */
class BattleResultCalculatorTest extends TestCase
{
    /** Verify effective stats combine base stats with equipment bonuses. */
    public function testEffectiveStatsWithEquipment(): void
    {
        $calculator = $this->createCalculatorWithStats(10, 5, 8, equippedBonuses: [
            ['stat' => StatType::Strength, 'points' => 3],
            ['stat' => StatType::Dexterity, 'points' => 2],
        ]);

        $user = $this->createMock(User::class);
        $result = $calculator->calculateEffectiveStats($user);

        $this->assertSame(13, $result['str']);
        $this->assertSame(7, $result['dex']);
        $this->assertSame(8, $result['con']);
    }

    /** Verify effective stats include passive skill bonuses. */
    public function testEffectiveStatsWithPassiveSkills(): void
    {
        $calculator = $this->createCalculatorWithStats(10, 5, 8, passiveSkillBonuses: [
            ['stat' => StatType::Constitution, 'points' => 5],
        ]);

        $user = $this->createMock(User::class);
        $result = $calculator->calculateEffectiveStats($user);

        $this->assertSame(10, $result['str']);
        $this->assertSame(5, $result['dex']);
        $this->assertSame(13, $result['con']);
    }

    /** Verify active skills are only added when their slug is in usedSkillSlugs. */
    public function testEffectiveStatsWithActiveSkillUsed(): void
    {
        $calculator = $this->createCalculatorWithStats(10, 5, 8, activeSkillBonuses: [
            ['slug' => 'power-surge', 'stat' => StatType::Strength, 'points' => 7],
        ]);

        $user = $this->createMock(User::class);

        // Without using the skill
        $result = $calculator->calculateEffectiveStats($user, [], []);
        $this->assertSame(10, $result['str']);

        // With using the skill
        $result = $calculator->calculateEffectiveStats($user, ['power-surge'], []);
        $this->assertSame(17, $result['str']);
    }

    /** Verify consumable bonuses are applied from usedConsumableSlugs. */
    public function testEffectiveStatsWithConsumables(): void
    {
        $calculator = $this->createCalculatorWithStats(10, 5, 8, consumableBonuses: [
            ['slug' => 'strength-potion', 'stat' => StatType::Strength, 'points' => 4],
        ]);

        $user = $this->createMock(User::class);
        $result = $calculator->calculateEffectiveStats($user, [], ['strength-potion']);

        $this->assertSame(14, $result['str']);
        $this->assertSame(5, $result['dex']);
        $this->assertSame(8, $result['con']);
    }

    /** Verify volume calculation sums weight * reps across all sets. */
    public function testVolumeCalculation(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        $exercises = [
            [
                'exerciseSlug' => 'barbell-bench-press',
                'sets' => [
                    ['weight' => 60.0, 'reps' => 10],
                    ['weight' => 60.0, 'reps' => 8],
                ],
            ],
        ];

        $volume = $calculator->calculateTrainingVolume($exercises);
        // 60*10 + 60*8 = 600 + 480 = 1080
        $this->assertEqualsWithDelta(1080.0, $volume, 0.01);
    }

    /** Verify anomaly filter caps weight at 300kg. */
    public function testVolumeAnomalyFilterCapsWeight(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        $exercises = [
            [
                'exerciseSlug' => 'test',
                'sets' => [
                    ['weight' => 500.0, 'reps' => 10], // Should be capped at 300
                ],
            ],
        ];

        $volume = $calculator->calculateTrainingVolume($exercises);
        // 300 * 10 = 3000 (not 500 * 10 = 5000)
        $this->assertEqualsWithDelta(3000.0, $volume, 0.01);
    }

    /** Verify anomaly filter caps reps at 100. */
    public function testVolumeAnomalyFilterCapsReps(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        $exercises = [
            [
                'exerciseSlug' => 'test',
                'sets' => [
                    ['weight' => 50.0, 'reps' => 200], // Should be capped at 100
                ],
            ],
        ];

        $volume = $calculator->calculateTrainingVolume($exercises);
        // 50 * 100 = 5000 (not 50 * 200 = 10000)
        $this->assertEqualsWithDelta(5000.0, $volume, 0.01);
    }

    /** Verify negative weight and reps are clamped to zero. */
    public function testVolumeAnomalyFilterClampsNegatives(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        $exercises = [
            [
                'exerciseSlug' => 'test',
                'sets' => [
                    ['weight' => -10.0, 'reps' => -5],
                ],
            ],
        ];

        $volume = $calculator->calculateTrainingVolume($exercises);
        $this->assertEqualsWithDelta(0.0, $volume, 0.01);
    }

    /** Verify damage calculation uses tick system based on duration. */
    public function testDamageCalculationPerTick(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        // 600 seconds = 60 ticks (at 6 ticks/min = 1 tick per 10s)
        // Strength activity: STR * 0.8 + CON * 0.2 = 10*0.8 + 5*0.2 = 9.0
        // 60 ticks * 9.0 * 1.0 (base multiplier) = 540 + trainingScore
        $damage = $calculator->calculateDamage(600, ['str' => 10, 'dex' => 5, 'con' => 5], 'strength', 0.0);
        $this->assertSame(540, $damage);
    }

    /** Verify damage is zero when duration is zero. */
    public function testDamageZeroForZeroDuration(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        $damage = $calculator->calculateDamage(0, ['str' => 10, 'dex' => 5, 'con' => 5], 'strength', 0.0);
        $this->assertSame(0, $damage);
    }

    /** Verify training score is added to tick damage. */
    public function testDamageIncludesTrainingScore(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        // 60s = 6 ticks; dmg/tick for STR=10,CON=5: 10*0.8+5*0.2 = 9.0; 6*9 = 54
        // plus trainingScore of 100
        $damage = $calculator->calculateDamage(60, ['str' => 10, 'dex' => 5, 'con' => 5], 'strength', 100.0);
        $this->assertSame(154, $damage);
    }

    /** Verify mob count = floor(totalDamage / mobHp). */
    public function testMobCountCalculation(): void
    {
        $mobHp = 100;
        $totalDamage = 350;

        $mobsDefeated = (int) floor($totalDamage / $mobHp);
        $this->assertSame(3, $mobsDefeated);
    }

    /** Verify completion < 50% = failed tier, 0 XP. */
    public function testPerformanceTierFailed(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        $plan = $this->createMockPlan();
        $result = $calculator->determinePerformanceTier(
            30.0, 100, 2, BattleMode::Recommended, $plan, 100, 200,
        );

        $this->assertSame('failed', $result['tier']);
        $this->assertSame(0, $result['xpAwarded']);
        $this->assertFalse($result['lootEarned']);
        $this->assertFalse($result['superLootEarned']);
    }

    /** Verify completion 50-75% = survived tier, base XP. */
    public function testPerformanceTierSurvived(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        $plan = $this->createMockPlan();
        $result = $calculator->determinePerformanceTier(
            60.0, 100, 2, BattleMode::Recommended, $plan, 100, 200,
        );

        $this->assertSame('survived', $result['tier']);
        $this->assertSame(100, $result['xpAwarded']);
        $this->assertFalse($result['lootEarned']);
    }

    /** Verify completion 75-100% = completed tier, loot earned. */
    public function testPerformanceTierCompleted(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        $plan = $this->createMockPlan();
        $result = $calculator->determinePerformanceTier(
            87.5, 325, 5, BattleMode::Recommended, $plan, 100, 500,
        );

        $this->assertSame('completed', $result['tier']);
        $this->assertSame(325, $result['xpAwarded']);
        $this->assertTrue($result['lootEarned']);
        $this->assertFalse($result['superLootEarned']);
    }

    /** Verify completion >= 100% in normal mode = exceeded, bonus XP. */
    public function testPerformanceTierExceeded(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        $plan = $this->createMockPlan();
        $result = $calculator->determinePerformanceTier(
            130.0, 500, 7, BattleMode::Recommended, $plan, 100, 700,
        );

        $this->assertSame('exceeded', $result['tier']);
        // Should have overperform bonus (at least base 10%)
        $this->assertGreaterThan(500, $result['xpAwarded']);
        $this->assertTrue($result['lootEarned']);
        $this->assertFalse($result['superLootEarned']);
    }

    /** Verify completion >= 100% in raid mode = raid_exceeded, super loot. */
    public function testPerformanceTierRaidExceeded(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        $plan = $this->createMockPlan();
        $result = $calculator->determinePerformanceTier(
            130.0, 500, 7, BattleMode::Raid, $plan, 100, 700,
        );

        $this->assertSame('raid_exceeded', $result['tier']);
        $this->assertTrue($result['lootEarned']);
        $this->assertTrue($result['superLootEarned']);
    }

    /** Verify difficulty modifier is set on next plan when user fails. */
    public function testDifficultyModifierOnPlan(): void
    {
        $plan = new WorkoutPlan();
        $this->assertEqualsWithDelta(1.0, $plan->getDifficultyModifier(), 0.001);

        $plan->setDifficultyModifier(0.8);
        $this->assertEqualsWithDelta(0.8, $plan->getDifficultyModifier(), 0.001);
    }

    /** Verify completion percent for cardio plan uses distance ratio. */
    public function testCompletionPercentCardio(): void
    {
        $calculator = $this->createCalculatorWithDefaults();

        $plan = $this->createMock(WorkoutPlan::class);
        $plan->method('getTargetDistance')->willReturn(5000.0);
        $plan->method('getExercises')->willReturn(new ArrayCollection());

        $healthData = ['distance' => 4000.0];
        $completion = $calculator->calculateCompletionPercent($plan, [], $healthData);

        $this->assertEqualsWithDelta(80.0, $completion, 0.1);
    }

    /** Verify BattleResult value object toArray includes all keys. */
    public function testBattleResultToArrayKeys(): void
    {
        $result = new \App\Application\Battle\DTO\BattleResult(
            performanceTier: 'completed',
            completionPercent: 87.5,
            mobsDefeated: 5,
            totalDamage: 4250,
            xpFromMobs: 325,
            bonusXpPercent: 0.0,
            xpAwarded: 325,
            lootEarned: true,
            superLootEarned: false,
            levelUp: false,
            newLevel: 15,
            totalXp: 18750,
            message: 'Victory! You completed the challenge.',
        );

        $array = $result->toArray();
        $this->assertArrayHasKey('performanceTier', $array);
        $this->assertArrayHasKey('completionPercent', $array);
        $this->assertArrayHasKey('mobsDefeated', $array);
        $this->assertArrayHasKey('totalDamage', $array);
        $this->assertArrayHasKey('xpFromMobs', $array);
        $this->assertArrayHasKey('bonusXpPercent', $array);
        $this->assertArrayHasKey('xpAwarded', $array);
        $this->assertArrayHasKey('lootEarned', $array);
        $this->assertArrayHasKey('superLootEarned', $array);
        $this->assertArrayHasKey('levelUp', $array);
        $this->assertArrayHasKey('newLevel', $array);
        $this->assertArrayHasKey('totalXp', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertSame('completed', $array['performanceTier']);
    }

    // ========================================================================
    // Factory helpers
    // ========================================================================

    /**
     * Create a BattleResultCalculator with default empty mocks and standard settings.
     */
    private function createCalculatorWithDefaults(): BattleResultCalculator
    {
        $gameSettingRepo = $this->createMock(GameSettingRepository::class);
        $gameSettingRepo->method('getAllAsMap')->willReturn([
            'battle_tick_frequency' => '6',
            'battle_base_damage_multiplier' => '1.0',
            'battle_strength_damage_factor' => '0.8',
            'battle_dex_damage_factor' => '0.8',
            'battle_con_damage_factor' => '0.7',
            'battle_random_variance' => '0.15',
            'battle_overperform_bonus' => '0.10',
            'battle_overperform_per_mob' => '0.05',
            'battle_raid_overperform_per_mob' => '0.10',
            'battle_fail_threshold' => '0.50',
            'battle_partial_threshold' => '0.75',
            'battle_success_threshold' => '1.00',
            'workout_volume_anomaly_max_weight' => '300',
            'workout_volume_anomaly_max_reps' => '100',
        ]);

        return new BattleResultCalculator(
            $gameSettingRepo,
            $this->createMock(CharacterStatsRepository::class),
            $this->createMock(ExerciseRepository::class),
            $this->createMock(UserInventoryRepository::class),
            $this->createMock(UserSkillRepository::class),
            $this->createMock(SkillRepository::class),
            $this->createMock(ItemCatalogRepository::class),
            $this->createMock(MobRepository::class),
            $this->createLevelingService(),
            $this->createMock(ExperienceLogRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createPsychModifierStub(),
        );
    }

    /** Psych multiplier stub — all calculator tests run with 1.0. */
    private function createPsychModifierStub(): \App\Application\PsychProfile\Service\PsychStatusModifierService
    {
        $stub = $this->createMock(\App\Application\PsychProfile\Service\PsychStatusModifierService::class);
        $stub->method('getXpMultiplier')->willReturn(1.0);

        return $stub;
    }

    /**
     * Create a calculator with specific base stats and optional bonus configurations.
     *
     * @param array<array{stat: StatType, points: int}>                           $equippedBonuses
     * @param array<array{stat: StatType, points: int}>                           $passiveSkillBonuses
     * @param array<array{slug: string, stat: StatType, points: int}>             $activeSkillBonuses
     * @param array<array{slug: string, stat: StatType, points: int}>             $consumableBonuses
     */
    private function createCalculatorWithStats(
        int $str,
        int $dex,
        int $con,
        array $equippedBonuses = [],
        array $passiveSkillBonuses = [],
        array $activeSkillBonuses = [],
        array $consumableBonuses = [],
    ): BattleResultCalculator {
        // Mock CharacterStatsRepository
        $stats = $this->createMock(CharacterStats::class);
        $stats->method('getStrength')->willReturn($str);
        $stats->method('getDexterity')->willReturn($dex);
        $stats->method('getConstitution')->willReturn($con);

        $characterStatsRepo = $this->createMock(CharacterStatsRepository::class);
        $characterStatsRepo->method('findByUser')->willReturn($stats);

        // Mock equipped items with stat bonuses
        $equippedItems = [];
        foreach ($equippedBonuses as $bonusData) {
            $bonus = $this->createMock(ItemStatBonus::class);
            $bonus->method('getStatType')->willReturn($bonusData['stat']);
            $bonus->method('getPoints')->willReturn($bonusData['points']);

            $catalog = $this->createMock(ItemCatalog::class);
            $catalog->method('getStatBonuses')->willReturn(new ArrayCollection([$bonus]));

            $inventory = $this->createMock(UserInventory::class);
            $inventory->method('getItemCatalog')->willReturn($catalog);

            $equippedItems[] = $inventory;
        }

        $userInventoryRepo = $this->createMock(UserInventoryRepository::class);
        $userInventoryRepo->method('findEquippedByUser')->willReturn($equippedItems);

        // Mock skills (passive and active)
        $userSkills = [];
        foreach ($passiveSkillBonuses as $bonusData) {
            $bonus = $this->createMock(SkillStatBonus::class);
            $bonus->method('getStatType')->willReturn($bonusData['stat']);
            $bonus->method('getPoints')->willReturn($bonusData['points']);

            $skill = $this->createMock(Skill::class);
            $skill->method('getSkillType')->willReturn('passive');
            $skill->method('getSlug')->willReturn('passive-skill');
            $skill->method('getStatBonuses')->willReturn(new ArrayCollection([$bonus]));

            $userSkill = $this->createMock(UserSkill::class);
            $userSkill->method('getSkill')->willReturn($skill);
            $userSkills[] = $userSkill;
        }

        foreach ($activeSkillBonuses as $bonusData) {
            $bonus = $this->createMock(SkillStatBonus::class);
            $bonus->method('getStatType')->willReturn($bonusData['stat']);
            $bonus->method('getPoints')->willReturn($bonusData['points']);

            $skill = $this->createMock(Skill::class);
            $skill->method('getSkillType')->willReturn('active');
            $skill->method('getSlug')->willReturn($bonusData['slug']);
            $skill->method('getStatBonuses')->willReturn(new ArrayCollection([$bonus]));

            $userSkill = $this->createMock(UserSkill::class);
            $userSkill->method('getSkill')->willReturn($skill);
            $userSkills[] = $userSkill;
        }

        $userSkillRepo = $this->createMock(UserSkillRepository::class);
        $userSkillRepo->method('findByUser')->willReturn($userSkills);

        // Mock consumables
        $itemCatalogRepo = $this->createMock(ItemCatalogRepository::class);
        foreach ($consumableBonuses as $bonusData) {
            $bonus = $this->createMock(ItemStatBonus::class);
            $bonus->method('getStatType')->willReturn($bonusData['stat']);
            $bonus->method('getPoints')->willReturn($bonusData['points']);

            $catalog = $this->createMock(ItemCatalog::class);
            $catalog->method('getStatBonuses')->willReturn(new ArrayCollection([$bonus]));

            $itemCatalogRepo->method('findBySlug')
                ->with($bonusData['slug'])
                ->willReturn($catalog);
        }

        $gameSettingRepo = $this->createMock(GameSettingRepository::class);
        $gameSettingRepo->method('getAllAsMap')->willReturn([]);

        return new BattleResultCalculator(
            $gameSettingRepo,
            $characterStatsRepo,
            $this->createMock(ExerciseRepository::class),
            $userInventoryRepo,
            $userSkillRepo,
            $this->createMock(SkillRepository::class),
            $itemCatalogRepo,
            $this->createMock(MobRepository::class),
            $this->createLevelingService(),
            $this->createMock(ExperienceLogRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createPsychModifierStub(),
        );
    }

    /**
     * Create a real LevelingService instance with mocked repositories.
     *
     * LevelingService is declared final and cannot be mocked by PHPUnit.
     */
    private function createLevelingService(): LevelingService
    {
        $gameSettingRepo = $this->createMock(GameSettingRepository::class);
        $gameSettingRepo->method('getAllAsMap')->willReturn([
            'level_formula_quad' => '4.2',
            'level_formula_linear' => '28',
            'level_max' => '100',
        ]);

        return new LevelingService(
            $gameSettingRepo,
            $this->createMock(CharacterStatsRepository::class),
            $this->createMock(ExperienceLogRepository::class),
        );
    }

    /** Create a mock WorkoutPlan with default values. */
    private function createMockPlan(): WorkoutPlan
    {
        $plan = $this->createMock(WorkoutPlan::class);
        $plan->method('getTargetDistance')->willReturn(null);
        $plan->method('getActivityType')->willReturn('strength');
        $plan->method('getExercises')->willReturn(new ArrayCollection());
        $plan->method('getDifficultyModifier')->willReturn(1.0);

        return $plan;
    }
}
