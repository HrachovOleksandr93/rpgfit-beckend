<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Domain\Test\Enum\ReasonEnum;
use App\Domain\User\Entity\User;
use App\Infrastructure\User\Repository\UserRepository;
use App\Security\Voter\AdminAnyVoter;
use App\Security\Voter\TesterSelfVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * Resolve the effective subject of a test-harness mutation.
 *
 * Controllers that accept an optional `asUserId` impersonation override
 * delegate here. The resolver enforces the role-based policy:
 *
 *   - `asUserId` present  -> must pass {@see AdminAnyVoter::ATTRIBUTE},
 *                            a valid `reason` is required, and the target
 *                            must exist (404 otherwise). Every resolution
 *                            is audited.
 *   - `asUserId` absent   -> must pass {@see TesterSelfVoter::ATTRIBUTE}
 *                            for the current user; non-testers are denied.
 *
 * Not `final`: matches the pattern of other Application services in this
 * codebase (see `BattleService`, `OnboardingService`) to keep the class
 * mockable in unit tests without awkward interface indirection.
 */
class TargetUserResolver
{
    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $userRepository,
        private readonly AdminActionLogService $auditLogger,
    ) {
    }

    /**
     * @throws AccessDeniedHttpException When the caller lacks the required role.
     * @throws HttpException              422 when `asUserId` is given without a valid reason.
     * @throws NotFoundHttpException      When the target UUID does not resolve to an existing user.
     */
    public function resolve(Request $request, User $currentUser): User
    {
        $asUserId = $this->readParam($request, 'asUserId');

        if ($asUserId === null || $asUserId === '') {
            // No override: only testers (and above) can mutate their own state.
            if (!$this->security->isGranted(TesterSelfVoter::ATTRIBUTE, $currentUser)) {
                throw new AccessDeniedHttpException('Not authorized to mutate test-harness state.');
            }

            return $currentUser;
        }

        // Impersonation path: requires ROLE_ADMIN hierarchy membership.
        if (!$this->security->isGranted(AdminAnyVoter::ATTRIBUTE, $asUserId)) {
            throw new AccessDeniedHttpException('Admin role required to mutate other users.');
        }

        $reason = $this->resolveReason($request);

        if (!Uuid::isValid($asUserId)) {
            throw new NotFoundHttpException(sprintf('User %s not found.', $asUserId));
        }

        $target = $this->userRepository->find(Uuid::fromString($asUserId));
        if (!$target instanceof User) {
            throw new NotFoundHttpException(sprintf('User %s not found.', $asUserId));
        }

        $this->auditLogger->record(
            $currentUser,
            $target,
            'target_user_resolve',
            $reason,
            [
                'as_user_id' => $asUserId,
                'path' => $request->getPathInfo(),
                'method' => $request->getMethod(),
            ],
        );

        return $target;
    }

    /**
     * Read a parameter preferring request attributes (set by earlier
     * listeners), then query string, then JSON body.
     */
    private function readParam(Request $request, string $name): ?string
    {
        if ($request->attributes->has($name)) {
            $value = $request->attributes->get($name);

            return is_scalar($value) ? (string) $value : null;
        }

        if ($request->query->has($name)) {
            return (string) $request->query->get($name);
        }

        $contentType = (string) $request->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/json')) {
            $body = $request->getContent();
            if ($body !== '') {
                $decoded = json_decode($body, true);
                if (is_array($decoded) && isset($decoded[$name]) && is_scalar($decoded[$name])) {
                    return (string) $decoded[$name];
                }
            }
        }

        if ($request->request->has($name)) {
            return (string) $request->request->get($name);
        }

        return null;
    }

    private function resolveReason(Request $request): ReasonEnum
    {
        $raw = $this->readParam($request, 'reason');
        if ($raw === null || $raw === '') {
            throw new HttpException(422, 'Missing `reason` parameter for admin impersonation.');
        }

        $reason = ReasonEnum::tryFrom($raw);
        if ($reason === null) {
            throw new HttpException(422, sprintf('Invalid `reason` value: %s', $raw));
        }

        return $reason;
    }
}
