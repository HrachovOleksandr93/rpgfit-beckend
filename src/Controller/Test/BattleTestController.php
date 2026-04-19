<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\DTO\SimulateBattleRequest;
use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\BattleTestService;
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
 * End-to-end battle simulation (spec §3.5).
 */
#[Route('/api/test')]
#[IsGranted('ROLE_TESTER')]
final class BattleTestController extends AbstractTestController
{
    public function __construct(
        TargetUserResolver $resolver,
        AdminActionLogService $audit,
        TestHarnessGate $gate,
        TestHarnessRateLimiter $rateLimiter,
        private readonly BattleTestService $battleTestService,
    ) {
        parent::__construct($resolver, $audit, $gate, $rateLimiter);
    }

    #[Route('/battle/simulate', name: 'api_test_battle_simulate', methods: ['POST'])]
    public function simulate(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'battle.simulate');

        $target = $this->resolveTarget($request, $currentUser);
        $dto = SimulateBattleRequest::fromArray($this->decodeBody($request));

        $result = $this->battleTestService->simulate($target, [
            'mobSlug' => $dto->mobSlug,
            'mobLevel' => $dto->mobLevel,
            'mode' => $dto->mode,
            'damageMultiplier' => $dto->damageMultiplier,
            'performanceTier' => $dto->performanceTier,
        ]);

        $payload = $result->toArray();
        $auditId = $this->audit($currentUser, $target, 'battle.simulate', [
            'input' => [
                'mobSlug' => $dto->mobSlug,
                'mode' => $dto->mode,
                'damageMultiplier' => $dto->damageMultiplier,
            ],
            'outcome' => [
                'performanceTier' => $result->performanceTier,
                'xpAwarded' => $result->xpAwarded,
                'mobsDefeated' => $result->mobsDefeated,
            ],
        ]);

        return $this->json($this->envelope($currentUser, $target, $auditId, $payload));
    }
}
