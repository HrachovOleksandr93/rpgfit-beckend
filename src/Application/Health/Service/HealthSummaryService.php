<?php

declare(strict_types=1);

namespace App\Application\Health\Service;

use App\Domain\Health\Enum\HealthDataType;
use App\Domain\User\Entity\User;
use App\Infrastructure\Health\Repository\HealthDataPointRepository;

final class HealthSummaryService
{
    public function __construct(
        private readonly HealthDataPointRepository $dataPointRepository,
    ) {
    }

    /**
     * Get a daily health summary for the given user and date.
     *
     * @return array<string, mixed>
     */
    public function getDailySummary(User $user, \DateTimeImmutable $date): array
    {
        $aggregated = $this->dataPointRepository->getAggregatedByDate($user, $date);

        $steps = 0.0;
        $activeEnergy = 0.0;
        $distance = 0.0;
        $sleepMinutes = 0.0;
        $heartRateSum = 0.0;
        $heartRateCount = 0;
        $workoutMinutes = 0.0;

        foreach ($aggregated as $row) {
            $type = $row['type'];

            // Handle both enum instances and string values
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
                case HealthDataType::SleepAsleep->value:
                case HealthDataType::SleepDeep->value:
                case HealthDataType::SleepLight->value:
                case HealthDataType::SleepRem->value:
                    $sleepMinutes += (float) $row['total'];
                    break;
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
