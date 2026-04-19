<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Application\Portal\Service\PortalSpawnService;
use App\Domain\Portal\Entity\Portal;
use App\Domain\Shared\Enum\Realm;
use App\Domain\User\Entity\User;

/**
 * Spawns a user-created portal at arbitrary coordinates without requiring
 * the `portal-creation-kit` item — the `force=1` flag is implicit here.
 *
 * Delegates to `PortalSpawnService::spawnUserCreatedPortal()` so portal
 * TTL / slug / expiration behaviour matches production.
 */
final class PortalTestService
{
    public function __construct(
        private readonly PortalSpawnService $portalSpawnService,
    ) {
    }

    public function spawnNearMe(
        User $user,
        float $latitude,
        float $longitude,
        int $radiusMeters,
        ?string $realm,
    ): Portal {
        $realmEnum = Realm::tryFrom((string) $realm) ?? $this->randomRealm();

        return $this->portalSpawnService->spawnUserCreatedPortal(
            user: $user,
            name: sprintf('Test portal %s', substr($user->getId()->toRfc4122(), 0, 4)),
            realm: $realmEnum,
            latitude: $latitude,
            longitude: $longitude,
            radiusM: max(1, min(5000, $radiusMeters)),
            tier: 1,
            challengeType: null,
            challengeParams: [],
            maxBattles: null,
            ttlHours: 1,
        );
    }

    private function randomRealm(): Realm
    {
        $cases = Realm::cases();

        return $cases[array_rand($cases)];
    }
}
