<?php

declare(strict_types=1);

namespace App\Application\Test\DTO;

/**
 * Request DTO for POST /api/test/portal/spawn-near-me.
 */
final class SpawnPortalRequest
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $radiusMeters,
        public readonly ?string $realm,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            latitude: (float) ($data['lat'] ?? $data['latitude'] ?? 0.0),
            longitude: (float) ($data['lng'] ?? $data['longitude'] ?? 0.0),
            radiusMeters: (int) ($data['radiusMeters'] ?? $data['radius_m'] ?? 200),
            realm: isset($data['realm']) ? (string) $data['realm'] : null,
        );
    }
}
