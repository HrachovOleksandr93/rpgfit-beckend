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
 * Authorizes an admin (or super-admin) to mutate any user's test-harness state.
 *
 * The subject follows the same shape as `TesterSelfVoter` (User | Uuid | string)
 * but the decision is based purely on the current user's ROLE_ADMIN hierarchy
 * membership — the target's identity is irrelevant.
 */
final class AdminAnyVoter extends Voter
{
    public const string ATTRIBUTE = 'ADMIN_CAN_MUTATE_ANY';

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

        return $this->security->isGranted(UserRole::ADMIN->value);
    }
}
