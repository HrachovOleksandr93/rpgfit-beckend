<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\TargetUserResolver;
use App\Application\Test\Service\TestHarnessGate;
use App\Application\Test\Service\TestHarnessRateLimiter;
use App\Domain\Test\Enum\ReasonEnum;
use App\Domain\User\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Base class shared by every `src/Controller/Test/*` controller.
 *
 * Collects the three cross-cutting dependencies every endpoint needs —
 * target resolution, audit logging, and the feature flag — so concrete
 * controllers stay short and focused on their domain service calls.
 *
 * Subclasses MUST call `assertEnabled()` before any mutation and MUST
 * resolve the target via `resolveTarget()` rather than reading the body.
 * `#[IsGranted('ROLE_TESTER')]` on the action delegates coarse-grained
 * role checks to Symfony Security.
 */
abstract class AbstractTestController extends AbstractController
{
    public function __construct(
        protected readonly TargetUserResolver $targetUserResolver,
        protected readonly AdminActionLogService $auditLogger,
        protected readonly TestHarnessGate $gate,
        protected readonly TestHarnessRateLimiter $rateLimiter,
    ) {
    }

    /**
     * Defence-in-depth duplicate of `TestHarnessKillSwitchListener`: even
     * if the listener is misconfigured, every mutation enforces the gate.
     */
    protected function assertEnabled(): void
    {
        if (!$this->gate->isEnabled()) {
            throw new NotFoundHttpException('Not found.');
        }
    }

    /** Resolve the user being mutated (self or admin-impersonated). */
    protected function resolveTarget(Request $request, User $currentUser): User
    {
        return $this->targetUserResolver->resolve($request, $currentUser);
    }

    /**
     * Persist one audit row after a successful mutation.
     *
     * @param array<string, mixed> $payload
     */
    protected function audit(
        User $actor,
        ?User $target,
        string $action,
        array $payload = [],
        ?ReasonEnum $reason = null,
    ): string {
        $log = $this->auditLogger->record($actor, $target, $action, $reason, $payload);

        return $log->getId()->toRfc4122();
    }

    /**
     * Apply the 60 req/min quota. Called by every action immediately after
     * `assertEnabled()`.
     */
    protected function enforceRateLimit(User $actor, string $endpoint): void
    {
        $this->rateLimiter->consume($actor, $endpoint);
    }

    /** Decode a JSON body, returning an empty array when the body is blank. */
    protected function decodeBody(Request $request): array
    {
        $raw = $request->getContent();
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Tri-state flag read: true when `?force=1` / `?force=true` present,
     * otherwise false. Controllers use this to decide whether to bypass
     * business-rule caps (never DB constraints).
     */
    protected function isForce(Request $request): bool
    {
        $raw = $request->query->get('force', '0');
        if (!is_string($raw)) {
            return false;
        }

        return in_array(strtolower($raw), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Build the standard envelope every test-harness response is wrapped
     * in: actor + target + auditLogId + the endpoint-specific payload.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function envelope(User $actor, ?User $target, string $auditLogId, array $data): array
    {
        return [
            'actor' => [
                'id' => $actor->getId()->toRfc4122(),
                'login' => $actor->getLogin(),
            ],
            'target' => $target !== null ? [
                'id' => $target->getId()->toRfc4122(),
                'login' => $target->getLogin(),
            ] : null,
            'auditLogId' => $auditLogId,
            'data' => $data,
        ];
    }
}
