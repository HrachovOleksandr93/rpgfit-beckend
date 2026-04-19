<?php

declare(strict_types=1);

namespace App\Application\Test\DTO;

/**
 * Request DTO for POST /api/test/workout/log.
 */
final class LogWorkoutRequest
{
    public function __construct(
        public readonly string $workoutType,
        public readonly float $durationMinutes,
        public readonly ?float $calories,
        public readonly ?float $distance,
        public readonly ?string $performedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            workoutType: (string) ($data['workoutType'] ?? 'generic'),
            durationMinutes: (float) ($data['durationMinutes'] ?? 0),
            calories: isset($data['calories']) ? (float) $data['calories'] : null,
            distance: isset($data['distance']) ? (float) $data['distance'] : null,
            performedAt: isset($data['performedAt']) ? (string) $data['performedAt'] : null,
        );
    }
}
