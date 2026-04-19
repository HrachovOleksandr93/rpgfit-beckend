<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\DTO\SpawnPortalRequest;
use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\PortalTestService;
use App\Application\Test\Service\TargetUserResolver;
use App\Application\Test\Service\TestHarnessGate;
use App\Application\Test\Service\TestHarnessRateLimiter;
use App\Domain\User\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Portal spawn helper (spec §3.6).
 *
 * Bypasses the `portal-creation-kit` inventory consumption the real
 * `/api/portals/dynamic` endpoint enforces — this is the whole point of
 * the test endpoint.
 */
#[Route('/api/test')]
#[IsGranted('ROLE_TESTER')]
final class PortalTestController extends AbstractTestController
{
    public function __construct(
        TargetUserResolver $resolver,
        AdminActionLogService $audit,
        TestHarnessGate $gate,
        TestHarnessRateLimiter $rateLimiter,
        private readonly PortalTestService $portalTestService,
    ) {
        parent::__construct($resolver, $audit, $gate, $rateLimiter);
    }

    #[Route('/portal/spawn-near-me', name: 'api_test_portal_spawn_near_me', methods: ['POST'])]
    public function spawnNearMe(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'portal.spawn');

        $target = $this->resolveTarget($request, $currentUser);
        $dto = SpawnPortalRequest::fromArray($this->decodeBody($request));

        $portal = $this->portalTestService->spawnNearMe(
            $target,
            $dto->latitude,
            $dto->longitude,
            $dto->radiusMeters,
            $dto->realm,
        );

        $payload = [
            'portalId' => $portal->getId()->toRfc4122(),
            'slug' => $portal->getSlug(),
            'lat' => $portal->getLatitude(),
            'lng' => $portal->getLongitude(),
            'realm' => $portal->getRealm()->value,
            'expiresAt' => $portal->getExpiresAt()?->format(\DateTimeInterface::ATOM),
        ];
        $auditId = $this->audit($currentUser, $target, 'portal.spawn', $payload);

        return $this->json($this->envelope($currentUser, $target, $auditId, $payload));
    }
}
