<?php

declare(strict_types=1);

namespace App\Application\Test\DTO;

/**
 * Request DTO for POST /api/test/level/set and POST /api/test/level/grant.
 */
final class SetLevelRequest
{
    public function __construct(
        public readonly int $level,
        public readonly int $steps,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            level: (int) ($data['level'] ?? 1),
            steps: (int) ($data['steps'] ?? 1),
        );
    }
}
