<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\Service\AdminActionLogService;
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
 * Kill-switch control surface (spec §11 Q7).
 *
 * `enable` / `disable` require superadmin so an ordinary admin can never
 * open the harness in production on their own. `status` is tester+ so the
 * RN debug drawer can ask "am I live?" without escalating.
 */
#[Route('/api/test/meta')]
final class MetaTestController extends AbstractTestController
{
    public function __construct(
        TargetUserResolver $resolver,
        AdminActionLogService $audit,
        TestHarnessGate $gate,
        TestHarnessRateLimiter $rateLimiter,
    ) {
        parent::__construct($resolver, $audit, $gate, $rateLimiter);
    }

    #[Route('/status', name: 'api_test_meta_status', methods: ['GET'])]
    #[IsGranted('ROLE_TESTER')]
    public function status(#[CurrentUser] User $currentUser): JsonResponse
    {
        // No assertEnabled() — the whole point of /meta/status is to
        // report whether the harness is live, including when it isn't.
        $this->enforceRateLimit($currentUser, 'meta.status');

        $enabled = $this->gate->isEnabled();
        $expiresAt = $this->gate->getSettingExpiresAt();

        $source = match (true) {
            $this->gate->isEnvEnabled() => 'env',
            $expiresAt !== null => 'game_setting',
            default => 'off',
        };

        return $this->json([
            'enabled' => $enabled,
            'ttlExpiresAt' => $expiresAt?->format(\DateTimeInterface::ATOM),
            'source' => $source,
        ]);
    }

    #[Route('/enable', name: 'api_test_meta_enable', methods: ['POST'])]
    #[IsGranted('ROLE_SUPERADMIN')]
    public function enable(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->enforceRateLimit($currentUser, 'meta.enable');

        $ttlMinutes = $request->query->getInt('ttl_min', 60);
        $expiresAt = $this->gate->enableForTtl($ttlMinutes);

        $payload = [
            'enabled' => true,
            'ttlExpiresAt' => $expiresAt->format(\DateTimeInterface::ATOM),
            'ttlMinutes' => $ttlMinutes,
        ];
        $auditId = $this->audit($currentUser, null, 'meta.enable', $payload);

        return $this->json($this->envelope($currentUser, null, $auditId, $payload));
    }

    #[Route('/disable', name: 'api_test_meta_disable', methods: ['POST'])]
    #[IsGranted('ROLE_SUPERADMIN')]
    public function disable(#[CurrentUser] User $currentUser): JsonResponse
    {
        $this->enforceRateLimit($currentUser, 'meta.disable');

        $this->gate->disable();
        $payload = ['enabled' => false];
        $auditId = $this->audit($currentUser, null, 'meta.disable', $payload);

        return $this->json($this->envelope($currentUser, null, $auditId, $payload));
    }
}
