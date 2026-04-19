<?php

declare(strict_types=1);

namespace App\Application\Test\DTO;

/**
 * Request DTO for POST /api/test/xp/grant.
 */
final class GrantXpRequest
{
    public function __construct(
        public readonly int $amount,
        public readonly string $source,
        public readonly ?string $description,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            amount: (int) ($data['amount'] ?? 0),
            source: (string) ($data['source'] ?? 'test_harness_grant'),
            description: isset($data['description']) ? (string) $data['description'] : null,
        );
    }
}
