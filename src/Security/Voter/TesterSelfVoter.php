<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\UserRole;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Authorizes a tester (or higher) to mutate their own test-harness state.
 *
 * The subject of the vote is the target user: either a `User` entity, a
 * `Uuid`, or a UUID string. The voter grants access iff the currently
 * authenticated user:
 *   - holds ROLE_TESTER (Symfony resolves the role hierarchy, so admins
 *     and super-admins satisfy this check automatically); AND
 *   - the target id equals the current user's id.
 *
 * Any other shape returns DENIED (never ABSTAIN) so the voter's decision
 * is unambiguous in a consistent-decision strategy.
 */
final class TesterSelfVoter extends Voter
{
    public const string ATTRIBUTE = 'TESTER_CAN_MUTATE_SELF';

    public function __construct(private readonly Security $security)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== self::ATTRIBUTE) {
            return false;
        }

        return $subject instanceof User
            || $subject instanceof Uuid
            || is_string($subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();
        if (!$currentUser instanceof User) {
            return false;
        }

        // Role hierarchy: admins and super-admins implicitly satisfy ROLE_TESTER.
        if (!$this->security->isGranted(UserRole::TESTER->value)) {
            return false;
        }

        $targetId = $this->resolveTargetId($subject);
        if ($targetId === null) {
            return false;
        }

        return $currentUser->getId()->toRfc4122() === $targetId;
    }

    private function resolveTargetId(mixed $subject): ?string
    {
        if ($subject instanceof User) {
            return $subject->getId()->toRfc4122();
        }
        if ($subject instanceof Uuid) {
            return $subject->toRfc4122();
        }
        if (is_string($subject) && Uuid::isValid($subject)) {
            return Uuid::fromString($subject)->toRfc4122();
        }

        return null;
    }
}
