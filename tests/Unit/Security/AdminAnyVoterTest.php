<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\UserRole;
use App\Security\Voter\AdminAnyVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for {@see AdminAnyVoter}.
 */
class AdminAnyVoterTest extends TestCase
{
    private function makeUser(string $login = 'actor@example.com'): User
    {
        $user = new User();
        $user->setLogin($login);
        $user->setPassword('hash');

        return $user;
    }

    private function makeToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testAdminOnAnyIsGranted(): void
    {
        $actor = $this->makeUser();
        $actor->addRole(UserRole::ADMIN);

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(UserRole::ADMIN->value)->willReturn(true);

        $voter = new AdminAnyVoter($security);
        $result = $voter->vote(
            $this->makeToken($actor),
            Uuid::v4()->toRfc4122(),
            [AdminAnyVoter::ATTRIBUTE],
        );

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testTesterIsDenied(): void
    {
        $actor = $this->makeUser();
        $actor->addRole(UserRole::TESTER);

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(UserRole::ADMIN->value)->willReturn(false);

        $voter = new AdminAnyVoter($security);
        $result = $voter->vote(
            $this->makeToken($actor),
            Uuid::v4()->toRfc4122(),
            [AdminAnyVoter::ATTRIBUTE],
        );

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testPlainUserIsDenied(): void
    {
        $actor = $this->makeUser();

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(UserRole::ADMIN->value)->willReturn(false);

        $voter = new AdminAnyVoter($security);
        $result = $voter->vote(
            $this->makeToken($actor),
            Uuid::v4()->toRfc4122(),
            [AdminAnyVoter::ATTRIBUTE],
        );

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }
}
