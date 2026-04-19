<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\AuditQueryService;
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
 * Read-only admin audit feed (spec §3.10).
 */
#[Route('/api/test/audit')]
#[IsGranted('ROLE_ADMIN')]
final class AuditTestController extends AbstractTestController
{
    public function __construct(
        TargetUserResolver $resolver,
        AdminActionLogService $audit,
        TestHarnessGate $gate,
        TestHarnessRateLimiter $rateLimiter,
        private readonly AuditQueryService $auditQueryService,
    ) {
        parent::__construct($resolver, $audit, $gate, $rateLimiter);
    }

    #[Route('/recent', name: 'api_test_audit_recent', methods: ['GET'])]
    public function recent(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'audit.recent');

        $limit = $request->query->getInt('limit', 50);
        $rows = $this->auditQueryService->recent($limit);

        return $this->json([
            'count' => count($rows),
            'items' => $rows,
        ]);
    }
}
