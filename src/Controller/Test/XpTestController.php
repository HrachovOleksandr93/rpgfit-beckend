<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\DTO\GrantXpRequest;
use App\Application\Test\DTO\SetLevelRequest;
use App\Application\Test\DTO\SetStatsRequest;
use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\StatsTestService;
use App\Application\Test\Service\TargetUserResolver;
use App\Application\Test\Service\TestHarnessGate;
use App\Application\Test\Service\TestHarnessRateLimiter;
use App\Application\Test\Service\XpTestService;
use App\Domain\User\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * XP / level / stat mutations (spec §3.2 + §3.8).
 */
#[Route('/api/test')]
#[IsGranted('ROLE_TESTER')]
final class XpTestController extends AbstractTestController
{
    public function __construct(
        TargetUserResolver $resolver,
        AdminActionLogService $audit,
        TestHarnessGate $gate,
        TestHarnessRateLimiter $rateLimiter,
        private readonly XpTestService $xpTestService,
        private readonly StatsTestService $statsTestService,
    ) {
        parent::__construct($resolver, $audit, $gate, $rateLimiter);
    }

    #[Route('/xp/grant', name: 'api_test_xp_grant', methods: ['POST'])]
    public function grantXp(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'xp.grant');

        $target = $this->resolveTarget($request, $currentUser);
        $dto = GrantXpRequest::fromArray($this->decodeBody($request));

        $force = $this->isForce($request);
        $result = $this->xpTestService->grantXp($target, $dto->amount, $force, $dto->source);

        $auditId = $this->audit($currentUser, $target, 'xp.grant', [
            'amount' => $dto->amount,
            'force' => $force,
            'source' => $dto->source,
            'result' => $result,
        ]);

        return $this->json($this->envelope($currentUser, $target, $auditId, $result));
    }

    #[Route('/level/set', name: 'api_test_level_set', methods: ['POST'])]
    public function setLevel(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'level.set');

        $target = $this->resolveTarget($request, $currentUser);
        $dto = SetLevelRequest::fromArray($this->decodeBody($request));

        $result = $this->xpTestService->setLevel($target, $dto->level);
        $auditId = $this->audit($currentUser, $target, 'level.set', $result);

        return $this->json($this->envelope($currentUser, $target, $auditId, $result));
    }

    #[Route('/level/grant', name: 'api_test_level_grant', methods: ['POST'])]
    public function grantLevels(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'level.grant');

        $target = $this->resolveTarget($request, $currentUser);
        $dto = SetLevelRequest::fromArray($this->decodeBody($request));

        $result = $this->xpTestService->grantLevels($target, $dto->steps, $this->isForce($request));
        $auditId = $this->audit($currentUser, $target, 'level.grant', $result);

        return $this->json($this->envelope($currentUser, $target, $auditId, $result));
    }

    #[Route('/stats/set', name: 'api_test_stats_set', methods: ['POST'])]
    public function setStats(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'stats.set');

        $target = $this->resolveTarget($request, $currentUser);
        $body = $this->decodeBody($request);
        $dto = SetStatsRequest::fromArray($body);

        $result = $this->statsTestService->setStats($target, $dto->str, $dto->dex, $dto->con);
        $auditId = $this->audit($currentUser, $target, 'stats.set', [
            'input' => ['str' => $dto->str, 'dex' => $dto->dex, 'con' => $dto->con],
            'result' => $result,
            'note' => 'state_machine_bypass',
        ]);

        return $this->json($this->envelope($currentUser, $target, $auditId, $result));
    }
}
