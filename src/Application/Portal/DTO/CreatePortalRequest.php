<?php

declare(strict_types=1);

namespace App\Application\Portal\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO for POST /api/portals/dynamic.
 *
 * The client sends the target location + optional challenge metadata.
 * The server validates that the caller owns a PortalCreationKit item,
 * consumes one, and creates a time-limited `user_created` portal.
 */
final class CreatePortalRequest
{
    #[Assert\NotBlank(message: 'Name is required.')]
    #[Assert\Length(min: 3, max: 120)]
    public string $name = '';

    #[Assert\NotBlank(message: 'Realm is required.')]
    public string $realm = '';

    #[Assert\NotNull(message: 'Latitude is required.')]
    #[Assert\Range(min: -90, max: 90)]
    public ?float $latitude = null;

    #[Assert\NotNull(message: 'Longitude is required.')]
    #[Assert\Range(min: -180, max: 180)]
    public ?float $longitude = null;

    #[Assert\Range(min: 20, max: 5000)]
    public int $radiusM = 100;

    #[Assert\Range(min: 1, max: 3)]
    public int $tier = 1;

    public ?string $challengeType = null;

    /** @var array<string, mixed> */
    public array $challengeParams = [];

    #[Assert\Range(min: 1, max: 50)]
    public ?int $maxBattles = 10;

    /** TTL in hours. Max 168 h = 7 days. */
    #[Assert\Range(min: 1, max: 168)]
    public int $ttlHours = 72;
}
