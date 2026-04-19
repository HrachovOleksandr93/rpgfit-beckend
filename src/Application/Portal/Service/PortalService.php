<?php

declare(strict_types=1);

namespace App\Application\Portal\Service;

use App\Application\Portal\DTO\CreatePortalRequest;
use App\Application\Portal\DTO\PortalDTO;
use App\Application\Portal\DTO\PortalListResponse;
use App\Domain\Portal\Entity\Portal;
use App\Domain\Portal\Enum\PortalType;
use App\Domain\Shared\Enum\Realm;
use App\Domain\User\Entity\User;
use App\Infrastructure\Inventory\Repository\UserInventoryRepository;
use App\Infrastructure\Portal\Repository\PortalRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Application service orchestrating portal queries and creation.
 *
 * Pure application logic (no HTTP concerns). Consumed by PortalController.
 * Dynamic portal spawn lifecycle lives in PortalSpawnService.
 */
final class PortalService
{
    /** Slug of the catalog item that must be consumed to create a user portal. */
    public const PORTAL_CREATION_KIT_SLUG = 'portal-creation-kit';

    public function __construct(
        private readonly PortalRepository $portalRepository,
        private readonly UserInventoryRepository $userInventoryRepository,
        private readonly PortalSpawnService $portalSpawnService,
    ) {
    }

    /**
     * List all static portals (no geo filter). Should be cached at the controller layer.
     */
    public function listStaticPortals(): PortalListResponse
    {
        $portals = $this->portalRepository->findAllStatic();
        $dtos = array_map(static fn (Portal $p) => PortalDTO::fromEntity($p), $portals);

        return new PortalListResponse(count: count($dtos), portals: $dtos);
    }

    /**
     * List portals within `radiusKm` of (lat, lng), sorted by distance.
     */
    public function listNearby(float $lat, float $lng, float $radiusKm, int $limit): PortalListResponse
    {
        $rows = $this->portalRepository->findWithinRadius($lat, $lng, $radiusKm, $limit);
        $dtos = array_map(
            static fn (array $row) => PortalDTO::fromEntity($row['portal'], $row['distanceKm']),
            $rows,
        );

        return new PortalListResponse(count: count($dtos), portals: $dtos);
    }

    /**
     * Fetch a single portal by slug and wrap it in a DTO, or return null.
     */
    public function getBySlug(string $slug): ?PortalDTO
    {
        $portal = $this->portalRepository->findBySlug($slug);

        return $portal !== null ? PortalDTO::fromEntity($portal) : null;
    }

    /**
     * Create a dynamic (user-created) portal after consuming one PortalCreationKit.
     *
     * Throws BadRequestHttpException if the user has no kit in inventory.
     */
    public function createUserPortal(User $user, CreatePortalRequest $request): PortalDTO
    {
        $realm = Realm::tryFrom($request->realm);
        if ($realm === null) {
            throw new BadRequestHttpException('Invalid realm.');
        }

        // Validate + consume the creation kit. We find the first non-deleted inventory row
        // whose catalog slug matches, decrement quantity or soft-delete it.
        $consumed = $this->consumeCreationKit($user);
        if (!$consumed) {
            throw new BadRequestHttpException('No Portal Creation Kit in inventory.');
        }

        $portal = $this->portalSpawnService->spawnUserCreatedPortal(
            user: $user,
            name: $request->name,
            realm: $realm,
            latitude: (float) $request->latitude,
            longitude: (float) $request->longitude,
            radiusM: $request->radiusM,
            tier: $request->tier,
            challengeType: $request->challengeType,
            challengeParams: $request->challengeParams,
            maxBattles: $request->maxBattles,
            ttlHours: $request->ttlHours,
        );

        return PortalDTO::fromEntity($portal);
    }

    /**
     * Scan the user's inventory for a PortalCreationKit and consume one unit.
     * Returns true on success.
     */
    private function consumeCreationKit(User $user): bool
    {
        foreach ($this->userInventoryRepository->findActiveByUser($user) as $inventoryItem) {
            if ($inventoryItem->getItemCatalog()->getSlug() !== self::PORTAL_CREATION_KIT_SLUG) {
                continue;
            }

            if ($inventoryItem->getQuantity() > 1) {
                $inventoryItem->setQuantity($inventoryItem->getQuantity() - 1);
            } else {
                $inventoryItem->setDeletedAt(new \DateTimeImmutable());
            }

            $this->userInventoryRepository->save($inventoryItem);

            return true;
        }

        return false;
    }

    /**
     * Safety clamp for the `type` filter on list queries — defaults to Static.
     */
    public function resolveTypeFilter(?string $rawType): ?PortalType
    {
        if ($rawType === null || $rawType === '') {
            return null;
        }

        return PortalType::tryFrom($rawType);
    }
}
