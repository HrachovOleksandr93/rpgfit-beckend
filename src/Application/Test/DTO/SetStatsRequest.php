<?php

declare(strict_types=1);

namespace App\Application\Test\DTO;

/**
 * Request DTO for POST /api/test/stats/set. Any stat may be omitted; only
 * fields present are modified.
 */
final class SetStatsRequest
{
    public function __construct(
        public readonly ?int $str,
        public readonly ?int $dex,
        public readonly ?int $con,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            str: isset($data['str']) ? (int) $data['str'] : null,
            dex: isset($data['dex']) ? (int) $data['dex'] : null,
            con: isset($data['con']) ? (int) $data['con'] : null,
        );
    }
}
