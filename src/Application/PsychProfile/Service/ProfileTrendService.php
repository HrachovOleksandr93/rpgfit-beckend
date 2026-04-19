<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\Service;

use App\Application\PsychProfile\DTO\TrendResponse;
use App\Domain\PsychProfile\Entity\PsychCheckIn;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\User\Entity\User;
use App\Infrastructure\PsychProfile\Repository\PsychCheckInRepository;

/**
 * Reads a user's check-in history and produces a trend snapshot.
 *
 * Application layer (PsychProfile bounded context). Read-only. Mirrors the
 * shape described by `TrendResponse` and §8 of the spec:
 *  - one point per calendar day in the window (oldest first)
 *  - each point carries the dominant status for that day (a row counts
 *    exactly once; when two check-ins land on the same day — which the
 *    one-per-day unique index rules out but we still guard defensively —
 *    the last-written wins)
 *  - aggregated { status => count } distribution across the window
 *  - a single dominantStatus over the whole window (or null when there's
 *    no data)
 *
 * Post-beta this service will also write back to `PsychUserProfile.trends`
 * via a nightly command; for now the controller consumes it synchronously.
 */
final class ProfileTrendService
{
    /** Allowed `window` query-param values. */
    public const ALLOWED_WINDOWS = [7, 30, 90];

    public function __construct(
        private readonly PsychCheckInRepository $checkInRepository,
    ) {
    }

    /**
     * Compute the trend snapshot for the given window in days.
     *
     * The method always returns the response DTO (never null). When the
     * user has no check-ins in the window the points list is empty,
     * distribution is `[]` and dominantStatus is `null`.
     */
    public function getTrend(User $user, int $windowDays): TrendResponse
    {
        $windowDays = $this->normaliseWindow($windowDays);

        $end = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $start = $end->modify(sprintf('-%d day', max(0, $windowDays - 1)));

        $rows = $this->checkInRepository->findInRange($user, $start, $end);

        // Build a date-indexed map (Y-m-d => PsychCheckIn) — later rows on
        // the same day override earlier ones, which matters only when the
        // DB-level unique index on (user_id, checked_in_on) is missing.
        $byDate = [];
        foreach ($rows as $row) {
            $byDate[$row->getCheckedInOn()->format('Y-m-d')] = $row;
        }

        $points = [];
        $distribution = [];
        foreach ($this->iterateDays($start, $end) as $day) {
            $key = $day->format('Y-m-d');
            if (!isset($byDate[$key])) {
                continue;
            }
            /** @var PsychCheckIn $checkIn */
            $checkIn = $byDate[$key];
            $status = $checkIn->getAssignedStatus()->value;
            $points[] = [
                'date' => $key,
                'status' => $status,
                'skipped' => $checkIn->isSkipped(),
            ];
            $distribution[$status] = ($distribution[$status] ?? 0) + 1;
        }

        return new TrendResponse(
            window: $windowDays,
            points: $points,
            distribution: $distribution,
            dominantStatus: $this->pickDominant($distribution),
        );
    }

    /**
     * Legacy array-shape helper kept for internal callers that don't want
     * the DTO (e.g. an upcoming nightly command that writes to `trends`).
     *
     * @return array{window: int, points: list<array{date: string, status: string, count: int}>}
     */
    public function getTrendArray(User $user, int $windowDays): array
    {
        $trend = $this->getTrend($user, $windowDays);

        $points = [];
        foreach ($trend->points as $point) {
            $points[] = [
                'date' => $point['date'],
                'status' => $point['status'],
                'count' => 1,
            ];
        }

        return [
            'window' => $trend->window,
            'points' => $points,
        ];
    }

    private function normaliseWindow(int $windowDays): int
    {
        if (in_array($windowDays, self::ALLOWED_WINDOWS, true)) {
            return $windowDays;
        }

        // Snap to the nearest allowed window — defensive guard for callers
        // that pass arbitrary values. Controller pre-validates, but the
        // service stays self-correcting.
        foreach (self::ALLOWED_WINDOWS as $allowed) {
            if ($windowDays <= $allowed) {
                return $allowed;
            }
        }

        return 90;
    }

    /**
     * Yield every date between `$start` and `$end` inclusive, oldest first.
     *
     * @return iterable<\DateTimeImmutable>
     */
    private function iterateDays(\DateTimeImmutable $start, \DateTimeImmutable $end): iterable
    {
        $cursor = $start;
        while ($cursor <= $end) {
            yield $cursor;
            $cursor = $cursor->modify('+1 day');
        }
    }

    /** @param array<string, int> $distribution */
    private function pickDominant(array $distribution): ?string
    {
        if ($distribution === []) {
            return null;
        }

        arsort($distribution);
        $top = array_key_first($distribution);

        // Defensive: guarantee the key is a valid PsychStatus value before
        // exposing it to the wire.
        return PsychStatus::tryFrom((string) $top)?->value;
    }
}
