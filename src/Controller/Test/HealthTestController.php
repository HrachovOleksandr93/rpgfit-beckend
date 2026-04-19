<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\DTO\InjectHealthRequest;
use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\HealthTestService;
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
 * Health data injection & cleanup (spec §3.3).
 */
#[Route('/api/test')]
#[IsGranted('ROLE_TESTER')]
final class HealthTestController extends AbstractTestController
{
    public function __construct(
        TargetUserResolver $resolver,
        AdminActionLogService $audit,
        TestHarnessGate $gate,
        TestHarnessRateLimiter $rateLimiter,
        private readonly HealthTestService $healthTestService,
    ) {
        parent::__construct($resolver, $audit, $gate, $rateLimiter);
    }

    #[Route('/health/inject', name: 'api_test_health_inject', methods: ['POST'])]
    public function inject(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'health.inject');

        $target = $this->resolveTarget($request, $currentUser);
        $dto = InjectHealthRequest::fromArray($this->decodeBody($request));

        $result = $this->healthTestService->injectPoints($target, $dto->platform, $dto->points);

        $auditId = $this->audit($currentUser, $target, 'health.inject', [
            'platform' => $dto->platform,
            'requestedPointCount' => count($dto->points),
            'result' => $result,
        ]);

        return $this->json($this->envelope($currentUser, $target, $auditId, $result));
    }

    #[Route('/health/clear', name: 'api_test_health_clear', methods: ['POST'])]
    public function clear(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'health.clear');

        $target = $this->resolveTarget($request, $currentUser);
        $body = $this->decodeBody($request);
        $dataType = isset($body['dataType']) ? (string) $body['dataType'] : null;

        $deleted = $this->healthTestService->clearTestPoints($target, $dataType);
        $payload = ['deletedCount' => $deleted, 'dataType' => $dataType];
        $auditId = $this->audit($currentUser, $target, 'health.clear', $payload);

        return $this->json($this->envelope($currentUser, $target, $auditId, $payload));
    }
}
