<?php

declare(strict_types=1);

namespace App\Application\Test\DTO;

/**
 * Request DTO for POST /api/test/event/trigger.
 */
final class TriggerEventRequest
{
    public function __construct(
        public readonly string $eventSlug,
        public readonly int $durationMin,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            eventSlug: (string) ($data['eventSlug'] ?? $data['event_slug'] ?? ''),
            durationMin: (int) ($data['durationMin'] ?? $data['duration_min'] ?? 30),
        );
    }
}
