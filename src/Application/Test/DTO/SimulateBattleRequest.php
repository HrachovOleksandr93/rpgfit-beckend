<?php

declare(strict_types=1);

namespace App\Application\Test\DTO;

/**
 * Request DTO for POST /api/test/battle/simulate.
 */
final class SimulateBattleRequest
{
    public function __construct(
        public readonly ?string $mobSlug,
        public readonly ?int $mobLevel,
        public readonly string $mode,
        public readonly float $damageMultiplier,
        public readonly ?string $performanceTier,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            mobSlug: isset($data['mobSlug']) ? (string) $data['mobSlug'] : null,
            mobLevel: isset($data['mobLevel']) ? (int) $data['mobLevel'] : null,
            mode: (string) ($data['mode'] ?? 'recommended'),
            damageMultiplier: (float) ($data['damageMultiplier'] ?? 1.0),
            performanceTier: isset($data['performanceTier']) ? (string) $data['performanceTier'] : null,
        );
    }
}
