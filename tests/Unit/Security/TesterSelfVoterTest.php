<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\UserRole;
use App\Security\Voter\TesterSelfVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Unit tests for {@see TesterSelfVoter}.
 *
 * Covers the four canonical policy slices:
 *   1. tester acting on self  -> GRANTED
 *   2. tester acting on other -> DENIED
 *   3. plain user             -> DENIED
 *   4. admin acting on other  -> GRANTED (hierarchy: ROLE_ADMIN >= ROLE_TESTER)
 */
class TesterSelfVoterTest extends TestCase
{
    private function makeUser(): User
    {
        $user = new User();
        $user->setLogin('actor@example.com');
        $user->setPassword('hash');

        return $user;
    }

    private function makeToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    public function testTesterOnSelfIsGranted(): void
    {
        $user = $this->makeUser();
        $user->addRole(UserRole::TESTER);

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(UserRole::TESTER->value)->willReturn(true);

        $voter = new TesterSelfVoter($security);
        $result = $voter->vote($this->makeToken($user), $user, [TesterSelfVoter::ATTRIBUTE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testTesterOnOtherIsDenied(): void
    {
        $actor = $this->makeUser();
        $actor->addRole(UserRole::TESTER);

        $target = new User();
        $target->setLogin('other@example.com');
        $target->setPassword('hash');

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(UserRole::TESTER->value)->willReturn(true);

        $voter = new TesterSelfVoter($security);
        $result = $voter->vote($this->makeToken($actor), $target, [TesterSelfVoter::ATTRIBUTE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testPlainUserIsDenied(): void
    {
        $user = $this->makeUser();

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(UserRole::TESTER->value)->willReturn(false);

        $voter = new TesterSelfVoter($security);
        $result = $voter->vote($this->makeToken($user), $user, [TesterSelfVoter::ATTRIBUTE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminOnOtherIsDeniedBecauseNotSelf(): void
    {
        // NOTE: Per spec, TESTER_CAN_MUTATE_SELF requires target == current user.
        // A pure admin acting on OTHER should be denied here — they must go
        // through AdminAnyVoter / ADMIN_CAN_MUTATE_ANY instead.
        $actor = $this->makeUser();
        $actor->addRole(UserRole::ADMIN);

        $target = new User();
        $target->setLogin('other@example.com');
        $target->setPassword('hash');

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(UserRole::TESTER->value)->willReturn(true);

        $voter = new TesterSelfVoter($security);
        $result = $voter->vote($this->makeToken($actor), $target, [TesterSelfVoter::ATTRIBUTE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminOnSelfIsGranted(): void
    {
        // Hierarchy: ROLE_ADMIN inherits ROLE_TESTER, so acting on self is allowed.
        $actor = $this->makeUser();
        $actor->addRole(UserRole::ADMIN);

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->with(UserRole::TESTER->value)->willReturn(true);

        $voter = new TesterSelfVoter($security);
        $result = $voter->vote($this->makeToken($actor), $actor, [TesterSelfVoter::ATTRIBUTE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }
}
