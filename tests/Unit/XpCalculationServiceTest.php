<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Character\Service\XpCalculationService;
use App\Domain\Health\Enum\HealthDataType;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for XpCalculationService: health data to XP conversion.
 */
class XpCalculationServiceTest extends TestCase
{
    private XpCalculationService $service;

    protected function setUp(): void
    {
        $repo = $this->createMock(GameSettingRepository::class);
        $repo->method('getAllAsMap')->willReturn([
            'xp_rate_steps' => '10',
            'xp_rate_active_energy' => '25',
            'xp_rate_workout' => '15',
            'xp_rate_distance' => '10',
            'xp_rate_sleep' => '10',
            'xp_rate_flights' => '5',
            'xp_daily_cap' => '3000',
            'xp_sleep_max_hours' => '9',
        ]);

        $this->service = new XpCalculationService($repo);
    }

    public function testXpFromSteps(): void
    {
        // 8000 steps / 1000 * 10 = 80 XP
        $xp = $this->service->calculateXpFromHealthData(HealthDataType::Steps, 8000.0);
        $this->assertSame(80, $xp);
    }

    public function testXpFromActiveEnergy(): void
    {
        // 350 kcal / 100 * 25 = 87.5 -> floor = 87 XP
        $xp = $this->service->calculateXpFromHealthData(HealthDataType::ActiveEnergyBurned, 350.0);
        $this->assertSame(87, $xp);
    }

    public function testXpFromWorkout(): void
    {
        // 45 min / 10 * 15 = 67.5 -> floor = 67 XP
        $xp = $this->service->calculateXpFromHealthData(HealthDataType::Workout, 45.0);
        $this->assertSame(67, $xp);
    }

    public function testXpFromDistance(): void
    {
        // 5000 metres / 1000 * 10 = 50 XP
        $xp = $this->service->calculateXpFromHealthData(HealthDataType::DistanceDelta, 5000.0);
        $this->assertSame(50, $xp);
    }

    public function testXpFromSleep(): void
    {
        // 480 minutes (8h) / 60 * 10 = 80 XP
        $xp = $this->service->calculateXpFromHealthData(HealthDataType::SleepDeep, 480.0);
        $this->assertSame(80, $xp);
    }

    public function testSleepCappedAtMaxHours(): void
    {
        // 720 minutes (12h) capped at 9h (540 min) -> 540/60 * 10 = 90 XP
        $xp = $this->service->calculateXpFromHealthData(HealthDataType::SleepAsleep, 720.0);
        $this->assertSame(90, $xp);
    }

    public function testXpFromFlightsClimbed(): void
    {
        // 10 flights * 5 = 50 XP
        $xp = $this->service->calculateXpFromHealthData(HealthDataType::FlightsClimbed, 10.0);
        $this->assertSame(50, $xp);
    }

    public function testNonXpTypeReturnsZero(): void
    {
        $this->assertSame(0, $this->service->calculateXpFromHealthData(HealthDataType::HeartRate, 72.0));
        $this->assertSame(0, $this->service->calculateXpFromHealthData(HealthDataType::Weight, 75.0));
        $this->assertSame(0, $this->service->calculateXpFromHealthData(HealthDataType::Height, 180.0));
        $this->assertSame(0, $this->service->calculateXpFromHealthData(HealthDataType::BodyFatPercentage, 15.0));
        $this->assertSame(0, $this->service->calculateXpFromHealthData(HealthDataType::BloodOxygen, 98.0));
        $this->assertSame(0, $this->service->calculateXpFromHealthData(HealthDataType::Water, 2000.0));
    }

    public function testDailyXpCapIsApplied(): void
    {
        // Create data that would exceed 3000 XP cap
        $points = [
            ['type' => HealthDataType::Steps, 'value' => 100_000.0],     // 1000 XP
            ['type' => HealthDataType::ActiveEnergyBurned, 'value' => 5000.0], // 1250 XP
            ['type' => HealthDataType::Workout, 'value' => 300.0],       // 450 XP
            ['type' => HealthDataType::DistanceDelta, 'value' => 50_000.0], // 500 XP
        ];
        // Total without cap: 3200; with cap: 3000
        $xp = $this->service->calculateDailyXp($points);
        $this->assertSame(3000, $xp);
    }

    public function testDailyXpBelowCap(): void
    {
        $points = [
            ['type' => HealthDataType::Steps, 'value' => 8000.0], // 80 XP
        ];
        $xp = $this->service->calculateDailyXp($points);
        $this->assertSame(80, $xp);
    }
}
