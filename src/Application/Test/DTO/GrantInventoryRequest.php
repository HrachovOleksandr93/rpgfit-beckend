<?php

declare(strict_types=1);

namespace App\Application\Test\DTO;

/**
 * Request DTO for POST /api/test/inventory/grant.
 */
final class GrantInventoryRequest
{
    public function __construct(
        public readonly string $itemSlug,
        public readonly int $quantity,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            itemSlug: (string) ($data['itemSlug'] ?? $data['slug'] ?? ''),
            quantity: (int) ($data['quantity'] ?? 1),
        );
    }
}
