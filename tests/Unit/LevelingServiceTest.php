<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Character\Service\LevelingService;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LevelingService: XP curve, level lookup, and progress calculation.
 */
class LevelingServiceTest extends TestCase
{
    private LevelingService $service;

    protected function setUp(): void
    {
        // Provide default settings via a mocked repository
        $repo = $this->createMock(GameSettingRepository::class);
        $repo->method('getAllAsMap')->willReturn([
            'level_formula_quad' => '4.2',
            'level_formula_linear' => '28',
            'level_max' => '100',
        ]);

        $statsRepo = $this->createMock(CharacterStatsRepository::class);
        $xpLogRepo = $this->createMock(ExperienceLogRepository::class);

        $this->service = new LevelingService($repo, $statsRepo, $xpLogRepo);
    }

    public function testXpForLevelOne(): void
    {
        // floor(4.2 * 1 + 28 * 1) = floor(32.2) = 32
        $this->assertSame(32, $this->service->getXpForLevel(1));
    }

    public function testXpForLevelTwo(): void
    {
        // floor(4.2 * 4 + 28 * 2) = floor(16.8 + 56) = floor(72.8) = 72
        $this->assertSame(72, $this->service->getXpForLevel(2));
    }

    public function testXpForLevelTen(): void
    {
        // floor(4.2 * 100 + 28 * 10) = floor(420 + 280) = 700
        $this->assertSame(700, $this->service->getXpForLevel(10));
    }

    public function testXpForLevelFifty(): void
    {
        // floor(4.2 * 2500 + 28 * 50) = floor(10500 + 1400) = 11900
        $this->assertSame(11900, $this->service->getXpForLevel(50));
    }

    public function testXpForLevelHundred(): void
    {
        // floor(4.2 * 10000 + 28 * 100) = floor(42000 + 2800) = 44800
        $this->assertSame(44800, $this->service->getXpForLevel(100));
    }

    public function testTotalXpForLevelSumsCorrectly(): void
    {
        $expected = 0;
        for ($i = 1; $i <= 10; $i++) {
            $expected += $this->service->getXpForLevel($i);
        }

        $this->assertSame($expected, $this->service->getTotalXpForLevel(10));
    }

    public function testGetLevelForTotalXpReturnsLevelOne(): void
    {
        // 0 XP should still be level 1 (minimum)
        $this->assertSame(1, $this->service->getLevelForTotalXp(0));
    }

    public function testGetLevelForTotalXpAtExactBoundary(): void
    {
        // Exactly enough XP for level 1 -> should be level 1
        $xpForLevel1 = $this->service->getXpForLevel(1); // 32
        $this->assertSame(1, $this->service->getLevelForTotalXp($xpForLevel1));
    }

    public function testGetLevelForTotalXpJustBelowLevel3(): void
    {
        $total = $this->service->getTotalXpForLevel(2);
        // One XP less than level 3 boundary should still be level 2
        $this->assertSame(2, $this->service->getLevelForTotalXp($total));
    }

    public function testGetLevelForTotalXpAtMaxReturns100(): void
    {
        // Huge XP should cap at level 100
        $this->assertSame(100, $this->service->getLevelForTotalXp(999_999_999));
    }

    public function testLevelProgressAtZeroXp(): void
    {
        $progress = $this->service->getLevelProgress(0);

        $this->assertSame(1, $progress['level']);
        $this->assertSame(0, $progress['currentLevelXp']);
        $this->assertGreaterThan(0, $progress['xpToNextLevel']);
        $this->assertSame(0.0, $progress['progressPercent']);
    }

    public function testLevelProgressPercentage(): void
    {
        // Give exactly the XP needed for level 1, plus half of level 2
        $xpLevel1 = $this->service->getXpForLevel(1);
        $xpLevel2 = $this->service->getXpForLevel(2);
        $totalXp = $xpLevel1 + (int) floor($xpLevel2 / 2);

        $progress = $this->service->getLevelProgress($totalXp);

        $this->assertSame(1, $progress['level']);
        $this->assertGreaterThan(0.0, $progress['progressPercent']);
        $this->assertLessThan(100.0, $progress['progressPercent']);
    }

    public function testFullLevelTableHas100Entries(): void
    {
        $table = $this->service->getFullLevelTable();

        $this->assertCount(100, $table);
        $this->assertSame(1, $table[0]['level']);
        $this->assertSame(100, $table[99]['level']);
    }

    public function testFullLevelTableTotalXpIsIncreasing(): void
    {
        $table = $this->service->getFullLevelTable();

        for ($i = 1; $i < count($table); $i++) {
            $this->assertGreaterThan(
                $table[$i - 1]['totalXpRequired'],
                $table[$i]['totalXpRequired'],
            );
        }
    }
}
