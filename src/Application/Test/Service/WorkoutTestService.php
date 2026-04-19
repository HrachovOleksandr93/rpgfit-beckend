<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Domain\Training\Entity\WorkoutLog;
use App\Domain\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Logs manual workouts and emits synthetic HR streams for battle flows.
 *
 * Stream simulation simply drives `HealthTestService::injectPoints()` —
 * the heart-rate and step samples are what the battle tick loop consumes
 * as if they came from a live HealthKit observer.
 */
final class WorkoutTestService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HealthTestService $healthTestService,
    ) {
    }

    /**
     * Persist a single `WorkoutLog` row for the target user.
     *
     * @return array{workoutLogId: string}
     */
    public function logWorkout(
        User $user,
        string $workoutType,
        float $durationMinutes,
        ?float $calories,
        ?float $distance,
        ?\DateTimeImmutable $performedAt,
    ): array {
        $log = new WorkoutLog();
        $log->setUser($user);
        $log->setWorkoutType($workoutType);
        $log->setDurationMinutes(max(0.0, $durationMinutes));
        $log->setCaloriesBurned($calories);
        $log->setDistance($distance);
        $log->setPerformedAt($performedAt ?? new \DateTimeImmutable());

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return [
            'workoutLogId' => $log->getId()->toRfc4122(),
        ];
    }

    /**
     * Emit `$samples` synthetic heart-rate + steps samples spread evenly
     * over `$durationSeconds`, then persist a workout log summarising the
     * stream.
     *
     * @return array{insertedCount: int, workoutLogId: string, samplesEmitted: int}
     */
    public function simulateWorkoutStream(
        User $user,
        int $samples,
        int $durationSeconds,
        int $baseHeartRate,
    ): array {
        $samples = max(1, min(600, $samples));
        $durationSeconds = max(1, $durationSeconds);
        $baseHeartRate = max(40, min(220, $baseHeartRate));

        $now = new \DateTimeImmutable();
        $stepSeconds = max(1, (int) floor($durationSeconds / $samples));

        $rawPoints = [];
        for ($i = 0; $i < $samples; $i++) {
            $from = $now->modify(sprintf('-%d seconds', $durationSeconds - $i * $stepSeconds));
            $to = $from->modify(sprintf('+%d seconds', $stepSeconds));

            // Mild oscillation around the base HR so metrics aren't flat-lined.
            $hr = $baseHeartRate + (int) round(5 * sin($i * 0.6));
            $rawPoints[] = [
                'type' => 'HEART_RATE',
                'value' => $hr,
                'unit' => 'bpm',
                'dateFrom' => $from->format(\DateTimeInterface::ATOM),
                'dateTo' => $to->format(\DateTimeInterface::ATOM),
            ];

            // Also emit a step sample so the XP pipeline has something to award.
            $rawPoints[] = [
                'type' => 'STEPS',
                'value' => 110,
                'unit' => 'count',
                'dateFrom' => $from->format(\DateTimeInterface::ATOM),
                'dateTo' => $to->format(\DateTimeInterface::ATOM),
            ];
        }

        $injection = $this->healthTestService->injectPoints($user, 'ios', $rawPoints);

        $workout = $this->logWorkout(
            $user,
            workoutType: 'simulated_stream',
            durationMinutes: $durationSeconds / 60,
            calories: null,
            distance: null,
            performedAt: $now,
        );

        return [
            'insertedCount' => $injection['insertedCount'],
            'workoutLogId' => $workout['workoutLogId'],
            'samplesEmitted' => $samples,
        ];
    }
}
