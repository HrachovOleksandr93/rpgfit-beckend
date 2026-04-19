<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\TargetUserResolver;
use App\Application\Test\Service\TestHarnessGate;
use App\Application\Test\Service\TestHarnessRateLimiter;
use App\Application\Test\Service\UserStateTestService;
use App\Domain\User\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * User state dump + reset (spec §3.9).
 *
 * Hard reset additionally removes `linked_account` rows (founder decision
 * Q5 in spec §11).
 */
#[Route('/api/test')]
#[IsGranted('ROLE_TESTER')]
final class UserTestController extends AbstractTestController
{
    public function __construct(
        TargetUserResolver $resolver,
        AdminActionLogService $audit,
        TestHarnessGate $gate,
        TestHarnessRateLimiter $rateLimiter,
        private readonly UserStateTestService $userStateTestService,
    ) {
        parent::__construct($resolver, $audit, $gate, $rateLimiter);
    }

    #[Route('/user/state', name: 'api_test_user_state', methods: ['GET'])]
    public function state(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'user.state');

        $target = $this->resolveTarget($request, $currentUser);
        $payload = $this->userStateTestService->dumpState($target);

        // Even a read is auditable when the target is not the caller.
        $auditId = $this->audit($currentUser, $target, 'user.state', [
            'targetId' => $target->getId()->toRfc4122(),
        ]);

        return $this->json($this->envelope($currentUser, $target, $auditId, $payload));
    }

    #[Route('/user/reset', name: 'api_test_user_reset', methods: ['POST'])]
    public function reset(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'user.reset');

        $target = $this->resolveTarget($request, $currentUser);
        $hard = $request->query->getBoolean('hard');
        $deleted = $this->userStateTestService->reset($target, $hard);

        $payload = ['reset' => true, 'hard' => $hard, 'deletedCounts' => $deleted];
        $auditId = $this->audit($currentUser, $target, 'user.reset', $payload);

        return $this->json($this->envelope($currentUser, $target, $auditId, $payload));
    }
}
