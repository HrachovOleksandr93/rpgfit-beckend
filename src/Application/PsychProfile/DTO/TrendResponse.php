<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\DTO;

/**
 * Response for GET /api/psych/trend?window=7|30|90.
 *
 * Application layer DTO. `points` is a list of daily status records
 * within the window (oldest first); `distribution` is an aggregate
 * { STATUS => count } map suited for quick badge math on the client.
 */
final class TrendResponse
{
    /**
     * @param list<array{date: string, status: string, skipped: bool}> $points
     * @param array<string, int>                                        $distribution
     */
    public function __construct(
        public readonly int $window,
        public readonly array $points,
        public readonly array $distribution,
        public readonly ?string $dominantStatus,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'window' => $this->window,
            'points' => $this->points,
            'distribution' => $this->distribution,
            'dominantStatus' => $this->dominantStatus,
        ];
    }
}
