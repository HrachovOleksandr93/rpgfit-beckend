<?php

declare(strict_types=1);

namespace App\Application\Health\Service;

use App\Domain\Health\Enum\HealthDataType;
use App\Domain\User\Entity\User;
use App\Infrastructure\Health\Repository\HealthDataPointRepository;

/**
 * Application service that aggregates daily health metrics for a user.
 *
 * Application layer (Health bounded context). Reads raw HealthDataPoint records
 * from the database and produces a consolidated daily summary for the mobile app
 * dashboard.
 *
 * Called by: HealthController::summary() (GET /api/health/summary?date=YYYY-MM-DD)
 * Data source: health_data_points table (aggregated via SQL GROUP BY in repository)
 * Data destination: JSON response to the mobile app
 *
 * Aggregation logic:
 * - Steps, active energy, distance, workout minutes: SUM of all data points for the day
 * - Sleep: SUM across all sleep phases (asleep, deep, light, REM)
 * - Heart rate: AVG of all heart rate readings for the day
 */
final class HealthSummaryService
{
    public function __construct(
        private readonly HealthDataPointRepository $dataPointRepository,
    ) {
    }

    /**
     * Build a daily health summary from stored data points.
     *
     * @param User $user The authenticated user
     * @param \DateTimeImmutable $date The date to summarize (midnight to 23:59:59)
     * @return array<string, mixed> Aggregated metrics keyed by metric name
     */
    public function getDailySummary(User $user, \DateTimeImmutable $date): array
    {
        // Get pre-aggregated data from the repository (SQL SUM/AVG/COUNT grouped by data_type)
        $aggregated = $this->dataPointRepository->getAggregatedByDate($user, $date);

        $steps = 0.0;
        $activeEnergy = 0.0;
        $distance = 0.0;
        $sleepMinutes = 0.0;
        $heartRateSum = 0.0;
        $heartRateCount = 0;
        $workoutMinutes = 0.0;

        // Map each aggregated row to the appropriate summary field
        foreach ($aggregated as $row) {
            $type = $row['type'];

            // Handle both enum instances and string values (DBAL returns raw strings)
            $typeValue = $type instanceof HealthDataType ? $type->value : $type;

            switch ($typeValue) {
                case HealthDataType::Steps->value:
                    $steps = (float) $row['total'];
                    break;
                case HealthDataType::ActiveEnergyBurned->value:
                    $activeEnergy = (float) $row['total'];
                    break;
                case HealthDataType::DistanceDelta->value:
                    $distance = (float) $row['total'];
                    break;
                // All sleep phases are summed together for total sleep time
                case HealthDataType::SleepAsleep->value:
                case HealthDataType::SleepDeep->value:
                case HealthDataType::SleepLight->value:
                case HealthDataType::SleepRem->value:
                    $sleepMinutes += (float) $row['total'];
                    break;
                // Heart rate uses average (not sum) since individual readings vary
                case HealthDataType::HeartRate->value:
                    $heartRateSum = (float) $row['average'];
                    $heartRateCount = 1;
                    break;
                case HealthDataType::Workout->value:
                    $workoutMinutes = (float) $row['total'];
                    break;
            }
        }

        $averageHeartRate = $heartRateCount > 0 ? round($heartRateSum, 1) : 0.0;

        return [
            'date' => $date->format('Y-m-d'),
            'steps' => $steps,
            'active_energy' => $activeEnergy,
            'distance' => $distance,
            'sleep_minutes' => $sleepMinutes,
            'average_heart_rate' => $averageHeartRate,
            'workout_minutes' => $workoutMinutes,
        ];
    }
}
