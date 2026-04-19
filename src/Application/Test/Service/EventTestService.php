<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Domain\User\Entity\User;

/**
 * Spawns a named event out-of-schedule. Phase 6 stub — the D5 social
 * event service is not yet implemented (tracked in
 * `docs/superpowers/plans/2026-04-18-portals-mobs-races-landing-impl.md`
 * as a future deliverable). The controller still routes/audits the
 * request so Playwright can exercise the admin-only path, but the
 * payload is informational only until D5 ships.
 */
final class EventTestService
{
    /**
     * @return array{triggered: bool, eventSlug: string, durationMin: int, note: string}
     */
    public function trigger(User $actor, string $eventSlug, int $durationMin): array
    {
        // TODO(phase-d5): delegate to the real event-service once available.
        return [
            'triggered' => false,
            'eventSlug' => $eventSlug,
            'durationMin' => max(1, $durationMin),
            'note' => 'Event service not yet implemented (D5). Request recorded in audit log only.',
        ];
    }
}
