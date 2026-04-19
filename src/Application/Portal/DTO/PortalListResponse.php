<?php

declare(strict_types=1);

namespace App\Application\Portal\DTO;

/**
 * Envelope DTO for portal list endpoints.
 *
 * Keeps count + items separate so clients can distinguish empty results
 * from failed requests without another round-trip.
 */
final class PortalListResponse
{
    /**
     * @param list<PortalDTO> $portals
     */
    public function __construct(
        public readonly int $count,
        public readonly array $portals,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'count' => $this->count,
            'portals' => array_map(static fn (PortalDTO $p) => $p->toArray(), $this->portals),
        ];
    }
}
