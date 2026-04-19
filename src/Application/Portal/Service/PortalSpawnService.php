<?php

declare(strict_types=1);

namespace App\Application\Portal\Service;

use App\Domain\Portal\Entity\Portal;
use App\Domain\Portal\Enum\PortalType;
use App\Domain\Shared\Enum\Realm;
use App\Domain\User\Entity\User;
use App\Infrastructure\Portal\Repository\PortalRepository;

/**
 * Application service owning the lifecycle of dynamic and user-created portals.
 *
 * Keeps creation + expiration + cleanup rules in one place so that both
 * API-driven spawns (PortalService::createUserPortal) and scheduled
 * background jobs share the same logic.
 */
final class PortalSpawnService
{
    public function __construct(
        private readonly PortalRepository $portalRepository,
    ) {
    }

    /**
     * Create a user-created portal. Caller is responsible for charging
     * whatever inventory cost the spawn requires; this service only persists
     * the entity and sets the time-limited fields.
     */
    public function spawnUserCreatedPortal(
        User $user,
        string $name,
        Realm $realm,
        float $latitude,
        float $longitude,
        int $radiusM,
        int $tier,
        ?string $challengeType,
        array $challengeParams,
        ?int $maxBattles,
        int $ttlHours,
    ): Portal {
        $slug = $this->deriveSlug($name, $user);

        $portal = new Portal();
        $portal->setName($name)
            ->setSlug($slug)
            ->setType(PortalType::UserCreated)
            ->setRealm($realm)
            ->setLatitude($latitude)
            ->setLongitude($longitude)
            ->setRadiusM($radiusM)
            ->setTier(max(1, min(3, $tier)))
            ->setChallengeType($challengeType)
            ->setChallengeParams($challengeParams)
            ->setMaxBattles($maxBattles)
            ->setCreatedByUser($user)
            ->setExpiresAt((new \DateTimeImmutable())->modify(sprintf('+%d hours', max(1, $ttlHours))));

        $this->portalRepository->save($portal);

        return $portal;
    }

    /**
     * Delete portals whose expiresAt is in the past. Intended for a periodic job.
     * Returns the number of removed portals.
     */
    public function cleanupExpired(): int
    {
        $now = new \DateTimeImmutable();
        $qb = $this->portalRepository->createQueryBuilder('p')
            ->where('p.expiresAt IS NOT NULL')
            ->andWhere('p.expiresAt < :now')
            ->setParameter('now', $now);

        $removed = 0;
        foreach ($qb->getQuery()->getResult() as $portal) {
            $this->portalRepository->remove($portal);
            $removed++;
        }

        return $removed;
    }

    /**
     * Build a URL-safe unique slug. User-visible names are kept; user ID +
     * timestamp guarantee uniqueness.
     */
    private function deriveSlug(string $name, User $user): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? 'portal');
        $base = trim($base, '-');
        if ($base === '') {
            $base = 'portal';
        }

        return sprintf(
            'uc-%s-%s-%d',
            substr($base, 0, 80),
            substr($user->getId()->toRfc4122(), 0, 8),
            (new \DateTimeImmutable())->getTimestamp(),
        );
    }
}
