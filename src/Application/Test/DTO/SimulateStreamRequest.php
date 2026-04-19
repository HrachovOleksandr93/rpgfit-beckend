<?php

declare(strict_types=1);

namespace App\Application\Test\DTO;

/**
 * Request DTO for POST /api/test/workout/simulate-stream.
 */
final class SimulateStreamRequest
{
    public function __construct(
        public readonly int $samples,
        public readonly int $durationSeconds,
        public readonly int $heartRate,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            samples: (int) ($data['samples'] ?? 10),
            durationSeconds: (int) ($data['durationSeconds'] ?? 60),
            heartRate: (int) ($data['heartRate'] ?? 130),
        );
    }
}
