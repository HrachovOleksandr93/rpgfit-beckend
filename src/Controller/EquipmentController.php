<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Inventory\Service\EquipmentService;
use App\Domain\User\Entity\User;
use App\Infrastructure\Inventory\Repository\UserInventoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * API controller for equipment management (equip, unequip, list).
 *
 * Provides endpoints for the mobile app to manage character equipment.
 * Delegates slot rule enforcement to EquipmentService.
 *
 * Endpoints:
 * - POST /api/equipment/equip/{inventoryId}   — equip an inventory item
 * - POST /api/equipment/unequip/{inventoryId} — unequip an inventory item
 * - GET  /api/equipment                       — list all equipped items
 *
 * All endpoints require JWT authentication.
 */
class EquipmentController extends AbstractController
{
    public function __construct(
        private readonly EquipmentService $equipmentService,
        private readonly UserInventoryRepository $userInventoryRepository,
    ) {
    }

    /**
     * Equip an item from the user's inventory.
     *
     * Validates ownership, delegates to EquipmentService for slot rules.
     * Returns 200 on success with the equipped item details.
     */
    #[Route('/api/equipment/equip/{inventoryId}', name: 'api_equipment_equip', methods: ['POST'])]
    public function equip(string $inventoryId, #[CurrentUser] User $user): JsonResponse
    {
        $inventoryItem = $this->userInventoryRepository->find($inventoryId);

        if ($inventoryItem === null || $inventoryItem->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            return $this->json(
                ['error' => 'Inventory item not found.'],
                Response::HTTP_NOT_FOUND,
            );
        }

        try {
            $this->equipmentService->equip($user, $inventoryItem);
        } catch (\InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->json([
            'id' => $inventoryItem->getId()->toRfc4122(),
            'item' => $inventoryItem->getItemCatalog()->getName(),
            'slot' => $inventoryItem->getEquippedSlot()?->value,
            'equipped' => true,
        ]);
    }

    /**
     * Unequip a currently equipped item.
     *
     * Validates ownership and equipped status, then clears the slot.
     */
    #[Route('/api/equipment/unequip/{inventoryId}', name: 'api_equipment_unequip', methods: ['POST'])]
    public function unequip(string $inventoryId, #[CurrentUser] User $user): JsonResponse
    {
        $inventoryItem = $this->userInventoryRepository->find($inventoryId);

        if ($inventoryItem === null || $inventoryItem->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            return $this->json(
                ['error' => 'Inventory item not found.'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $this->equipmentService->unequip($user, $inventoryItem);

        return $this->json([
            'id' => $inventoryItem->getId()->toRfc4122(),
            'item' => $inventoryItem->getItemCatalog()->getName(),
            'equipped' => false,
        ]);
    }

    /**
     * List all currently equipped items for the authenticated user.
     *
     * Returns an array of equipped items with slot assignments.
     */
    #[Route('/api/equipment', name: 'api_equipment_list', methods: ['GET'])]
    public function list(#[CurrentUser] User $user): JsonResponse
    {
        $equippedItems = $this->equipmentService->getEquippedItems($user);

        $result = [];
        foreach ($equippedItems as $item) {
            $result[] = [
                'id' => $item->getId()->toRfc4122(),
                'item' => $item->getItemCatalog()->getName(),
                'slot' => $item->getEquippedSlot()?->value,
                'equipped' => true,
            ];
        }

        return $this->json($result);
    }
}
