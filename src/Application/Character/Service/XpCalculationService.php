<?php

declare(strict_types=1);

namespace App\Application\Character\Service;

use App\Domain\Health\Enum\HealthDataType;
use App\Infrastructure\Config\Repository\GameSettingRepository;

/**
 * Converts raw health data values into XP (experience points).
 *
 * Application layer (Character bounded context). Each health data type has a
 * configurable XP rate stored in the game_settings table. The conversion formulas
 * normalise raw units (steps, kcal, metres, minutes) into game-meaningful amounts.
 *
 * Non-XP health types (HeartRate, Weight, Height, BodyFat, BloodOxygen, Water)
 * are tracked for stats but do not award XP.
 */
final class XpCalculationService
{
    /** In-memory cache of the settings map for the current request. */
    private ?array $settingsCache = null;

    public function __construct(
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
    }

    /**
     * Calculate the XP reward for a single health data measurement.
     *
     * @param HealthDataType $type  The type of health metric
     * @param float          $value The raw value in the metric's native unit
     */
    public function calculateXpFromHealthData(HealthDataType $type, float $value): int
    {
        return match ($type) {
            // Steps: XP per 1,000 steps
            HealthDataType::Steps
                => (int) floor($value / 1000 * $this->getRate('xp_rate_steps')),

            // Active energy: XP per 100 kcal burned
            HealthDataType::ActiveEnergyBurned
                => (int) floor($value / 100 * $this->getRate('xp_rate_active_energy')),

            // Workout duration: XP per 10 minutes
            HealthDataType::Workout
                => (int) floor($value / 10 * $this->getRate('xp_rate_workout')),

            // Distance: value is in metres, XP per km
            HealthDataType::DistanceDelta
                => (int) floor($value / 1000 * $this->getRate('xp_rate_distance')),

            // Sleep types: value is in minutes, capped at max hours, XP per hour
            HealthDataType::SleepAsleep,
            HealthDataType::SleepDeep,
            HealthDataType::SleepLight,
            HealthDataType::SleepRem
                => (int) floor(
                    min($value, $this->getRate('xp_sleep_max_hours') * 60) / 60
                    * $this->getRate('xp_rate_sleep')
                ),

            // Flights climbed: XP per flight
            HealthDataType::FlightsClimbed
                => (int) floor($value * $this->getRate('xp_rate_flights')),

            // Non-XP types: tracked for health stats only
            default => 0,
        };
    }

    /**
     * Calculate total XP from a batch of health data points, capped at the daily maximum.
     *
     * @param array<array{type: HealthDataType, value: float}> $healthDataPoints
     */
    public function calculateDailyXp(array $healthDataPoints): int
    {
        $total = 0;
        foreach ($healthDataPoints as $point) {
            $total += $this->calculateXpFromHealthData($point['type'], $point['value']);
        }

        $cap = (int) $this->getRate('xp_daily_cap');

        return min($total, $cap);
    }

    /** Read a numeric rate from settings, defaulting to 0 if missing. */
    private function getRate(string $key): float
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = $this->gameSettingRepository->getAllAsMap();
        }

        return (float) ($this->settingsCache[$key] ?? '0');
    }
}
