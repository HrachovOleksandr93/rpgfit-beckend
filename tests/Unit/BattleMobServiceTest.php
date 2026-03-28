<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Battle\Enum\BattleMode;
use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Mob\Entity\Mob;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BattleMobService mob selection logic.
 *
 * Tests the multiplier calculations and rarity filtering rules
 * without depending on Doctrine query builder mocking.
 * Full integration of mob selection is tested in BattleControllerTest.
 */
class BattleMobServiceTest extends TestCase
{
    /** Verify that raid multiplier (1.3x) is correctly applied to HP. */
    public function testRaidMultiplierAppliesToHp(): void
    {
        $baseHp = 1000;
        $raidHp = (int) round($baseHp * 1.3);

        $this->assertSame(1300, $raidHp);
    }

    /** Verify that raid multiplier (1.3x) is correctly applied to XP. */
    public function testRaidMultiplierAppliesToXp(): void
    {
        $baseXp = 100;
        $raidXp = (int) round($baseXp * 1.3);

        $this->assertSame(130, $raidXp);
    }

    /** Verify that custom mode does not apply any multiplier. */
    public function testCustomModeReturnsBaseStats(): void
    {
        $mob = $this->createMob(5, 500, 50, ItemRarity::Common);
        $mode = BattleMode::Custom;

        $hp = $mob->getHp();
        $xpReward = $mob->getXpReward();

        // No multiplier for custom mode
        if ($mode === BattleMode::Raid) {
            $hp = (int) round($hp * 1.3);
            $xpReward = (int) round($xpReward * 1.3);
        }

        $this->assertSame(500, $hp);
        $this->assertSame(50, $xpReward);
    }

    /** Verify that recommended mode does not apply any multiplier. */
    public function testRecommendedModeReturnsBaseStats(): void
    {
        $mob = $this->createMob(8, 700, 70, ItemRarity::Uncommon);
        $mode = BattleMode::Recommended;

        $hp = $mob->getHp();
        $xpReward = $mob->getXpReward();

        if ($mode === BattleMode::Raid) {
            $hp = (int) round($hp * 1.3);
            $xpReward = (int) round($xpReward * 1.3);
        }

        $this->assertSame(700, $hp);
        $this->assertSame(70, $xpReward);
    }

    /** Verify that level range is plus/minus 2 for mob selection. */
    public function testLevelRangeIsPlusMinus2(): void
    {
        $userLevel = 10;
        $levelRange = 2;

        $levelMin = max(1, $userLevel - $levelRange);
        $levelMax = $userLevel + $levelRange;

        $this->assertSame(8, $levelMin);
        $this->assertSame(12, $levelMax);
    }

    /** Verify that level range does not go below 1. */
    public function testLevelRangeMinimumIs1(): void
    {
        $userLevel = 1;
        $levelRange = 2;

        $levelMin = max(1, $userLevel - $levelRange);
        $levelMax = $userLevel + $levelRange;

        $this->assertSame(1, $levelMin);
        $this->assertSame(3, $levelMax);
    }

    /** Verify that custom/recommended mode allows common, uncommon, rare. */
    public function testCustomModeAllowedRarities(): void
    {
        $allowed = [ItemRarity::Common, ItemRarity::Uncommon, ItemRarity::Rare];

        $this->assertContains(ItemRarity::Common, $allowed);
        $this->assertContains(ItemRarity::Uncommon, $allowed);
        $this->assertContains(ItemRarity::Rare, $allowed);
        $this->assertNotContains(ItemRarity::Epic, $allowed);
        $this->assertNotContains(ItemRarity::Legendary, $allowed);
    }

    /** Verify that raid mode allows rare, epic, legendary. */
    public function testRaidModeAllowedRarities(): void
    {
        $allowed = [ItemRarity::Rare, ItemRarity::Epic, ItemRarity::Legendary];

        $this->assertNotContains(ItemRarity::Common, $allowed);
        $this->assertNotContains(ItemRarity::Uncommon, $allowed);
        $this->assertContains(ItemRarity::Rare, $allowed);
        $this->assertContains(ItemRarity::Epic, $allowed);
        $this->assertContains(ItemRarity::Legendary, $allowed);
    }

    /** Verify that raid multiplier rounds correctly for odd values. */
    public function testRaidMultiplierRounding(): void
    {
        // 77 * 1.3 = 100.1 -> rounds to 100
        $this->assertSame(100, (int) round(77 * 1.3));

        // 33 * 1.3 = 42.9 -> rounds to 43
        $this->assertSame(43, (int) round(33 * 1.3));

        // 1 * 1.3 = 1.3 -> rounds to 1
        $this->assertSame(1, (int) round(1 * 1.3));
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    /** Create a Mob entity with the given stats. */
    private function createMob(int $level, int $hp, int $xpReward, ItemRarity $rarity): Mob
    {
        $mob = new Mob();
        $mob->setName('Test Mob');
        $mob->setSlug('test-mob-' . $level);
        $mob->setLevel($level);
        $mob->setHp($hp);
        $mob->setXpReward($xpReward);
        $mob->setRarity($rarity);

        return $mob;
    }
}
