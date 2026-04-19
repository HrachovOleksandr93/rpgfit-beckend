<?php

declare(strict_types=1);

namespace App\Application\Test\DTO;

/**
 * Request DTO for POST /api/test/health/inject. Shape mirrors the real
 * `/api/health/sync` payload but every point is tagged `source_app =
 * test-harness` server-side.
 */
final class InjectHealthRequest
{
    /**
     * @param list<array<string, mixed>> $points
     */
    public function __construct(
        public readonly string $platform,
        public readonly array $points,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $points = [];
        if (isset($data['points']) && is_array($data['points'])) {
            foreach ($data['points'] as $raw) {
                if (is_array($raw)) {
                    $points[] = $raw;
                }
            }
        }

        return new self(
            platform: (string) ($data['platform'] ?? 'ios'),
            points: $points,
        );
    }
}
