<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Application\Inventory\Service\EquipmentService;
use App\Domain\Inventory\Entity\UserInventory;
use App\Domain\User\Entity\User;
use App\Infrastructure\Inventory\Repository\ItemCatalogRepository;
use App\Infrastructure\Inventory\Repository\UserInventoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * Orchestrates inventory/equipment mutations for the test harness.
 *
 * Pure delegation: item rules live in `EquipmentService` already. This
 * layer is responsible for resolving slugs/uuids and for "bulk clear" /
 * "soft-delete cascade" scenarios the normal API does not need.
 */
final class InventoryTestService
{
    public function __construct(
        private readonly ItemCatalogRepository $itemCatalogRepository,
        private readonly UserInventoryRepository $userInventoryRepository,
        private readonly EquipmentService $equipmentService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Grant `$quantity` units of the given catalog item to the user.
     *
     * @return array{inventoryId: string, itemSlug: string, quantity: int}
     */
    public function grant(User $user, string $itemSlug, int $quantity): array
    {
        $quantity = max(1, min(999, $quantity));

        $catalog = $this->itemCatalogRepository->findBySlug($itemSlug);
        if ($catalog === null) {
            throw new NotFoundHttpException(sprintf('Unknown item slug "%s".', $itemSlug));
        }

        $inventory = new UserInventory();
        $inventory->setUser($user);
        $inventory->setItemCatalog($catalog);
        $inventory->setQuantity($quantity);

        $this->userInventoryRepository->save($inventory);

        return [
            'inventoryId' => $inventory->getId()->toRfc4122(),
            'itemSlug' => $itemSlug,
            'quantity' => $quantity,
        ];
    }

    /**
     * Soft-delete every active inventory row for the user, auto-unequipping
     * first. Returns the number of rows cleared.
     */
    public function clearAll(User $user): int
    {
        $items = $this->userInventoryRepository->findActiveByUser($user);
        $now = new \DateTimeImmutable();
        $cleared = 0;

        foreach ($items as $item) {
            if ($item->isEquipped()) {
                $this->equipmentService->unequip($user, $item);
            }
            $item->setDeletedAt($now);
            $this->entityManager->persist($item);
            $cleared++;
        }

        $this->entityManager->flush();

        return $cleared;
    }

    /**
     * Equip an owned inventory row. Validates that the row actually belongs
     * to the target user so an admin cannot accidentally equip an item from
     * a different account.
     */
    public function equip(User $user, string $inventoryId): UserInventory
    {
        $item = $this->loadOwnedInventoryItem($user, $inventoryId);
        $this->equipmentService->equip($user, $item);

        return $item;
    }

    /**
     * Unequip an owned inventory row.
     */
    public function unequip(User $user, string $inventoryId): UserInventory
    {
        $item = $this->loadOwnedInventoryItem($user, $inventoryId);
        $this->equipmentService->unequip($user, $item);

        return $item;
    }

    private function loadOwnedInventoryItem(User $user, string $inventoryId): UserInventory
    {
        if (!Uuid::isValid($inventoryId)) {
            throw new NotFoundHttpException('Invalid inventory id.');
        }

        $item = $this->userInventoryRepository->find(Uuid::fromString($inventoryId));
        if (!$item instanceof UserInventory) {
            throw new NotFoundHttpException('Inventory item not found.');
        }

        if ($item->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            // Hide cross-user existence behind a 404 — matches `/api/equipment`.
            throw new NotFoundHttpException('Inventory item not found.');
        }

        return $item;
    }
}
