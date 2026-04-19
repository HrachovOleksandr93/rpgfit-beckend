<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\DTO\GrantInventoryRequest;
use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\InventoryTestService;
use App\Application\Test\Service\TargetUserResolver;
use App\Application\Test\Service\TestHarnessGate;
use App\Application\Test\Service\TestHarnessRateLimiter;
use App\Domain\User\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Inventory/equipment mutations for the test harness.
 *
 * Endpoints (spec §3.1 + §3.7):
 *   POST /api/test/inventory/grant
 *   POST /api/test/inventory/clear
 *   POST /api/test/equipment/equip
 *   POST /api/test/equipment/unequip
 */
#[Route('/api/test')]
#[IsGranted('ROLE_TESTER')]
final class InventoryTestController extends AbstractTestController
{
    public function __construct(
        TargetUserResolver $resolver,
        AdminActionLogService $audit,
        TestHarnessGate $gate,
        TestHarnessRateLimiter $rateLimiter,
        private readonly InventoryTestService $inventoryTestService,
    ) {
        parent::__construct($resolver, $audit, $gate, $rateLimiter);
    }

    #[Route('/inventory/grant', name: 'api_test_inventory_grant', methods: ['POST'])]
    public function grant(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'inventory.grant');

        $target = $this->resolveTarget($request, $currentUser);
        $dto = GrantInventoryRequest::fromArray($this->decodeBody($request));
        if ($dto->itemSlug === '') {
            return $this->json(['error' => 'itemSlug is required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $this->inventoryTestService->grant($target, $dto->itemSlug, $dto->quantity);
        $auditId = $this->audit($currentUser, $target, 'inventory.grant', $result);

        return $this->json($this->envelope($currentUser, $target, $auditId, $result));
    }

    #[Route('/inventory/clear', name: 'api_test_inventory_clear', methods: ['POST'])]
    public function clear(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'inventory.clear');

        $target = $this->resolveTarget($request, $currentUser);
        $cleared = $this->inventoryTestService->clearAll($target);
        $payload = ['clearedCount' => $cleared];
        $auditId = $this->audit($currentUser, $target, 'inventory.clear', $payload);

        return $this->json($this->envelope($currentUser, $target, $auditId, $payload));
    }

    #[Route('/equipment/equip', name: 'api_test_equipment_equip', methods: ['POST'])]
    public function equip(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'equipment.equip');

        $target = $this->resolveTarget($request, $currentUser);
        $body = $this->decodeBody($request);
        $inventoryId = (string) ($body['inventoryId'] ?? '');
        if ($inventoryId === '') {
            return $this->json(['error' => 'inventoryId is required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $item = $this->inventoryTestService->equip($target, $inventoryId);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payload = [
            'inventoryId' => $item->getId()->toRfc4122(),
            'slot' => $item->getEquippedSlot()?->value,
            'equipped' => true,
        ];
        $auditId = $this->audit($currentUser, $target, 'equipment.equip', $payload);

        return $this->json($this->envelope($currentUser, $target, $auditId, $payload));
    }

    #[Route('/equipment/unequip', name: 'api_test_equipment_unequip', methods: ['POST'])]
    public function unequip(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'equipment.unequip');

        $target = $this->resolveTarget($request, $currentUser);
        $body = $this->decodeBody($request);
        $inventoryId = (string) ($body['inventoryId'] ?? '');
        if ($inventoryId === '') {
            return $this->json(['error' => 'inventoryId is required.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $item = $this->inventoryTestService->unequip($target, $inventoryId);
        $payload = [
            'inventoryId' => $item->getId()->toRfc4122(),
            'equipped' => false,
        ];
        $auditId = $this->audit($currentUser, $target, 'equipment.unequip', $payload);

        return $this->json($this->envelope($currentUser, $target, $auditId, $payload));
    }
}
