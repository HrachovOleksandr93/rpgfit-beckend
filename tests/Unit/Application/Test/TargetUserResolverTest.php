<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Test;

use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\TargetUserResolver;
use App\Domain\User\Entity\User;
use App\Infrastructure\User\Repository\UserRepository;
use App\Security\Voter\AdminAnyVoter;
use App\Security\Voter\TesterSelfVoter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * Unit tests for {@see TargetUserResolver}. Covers the five canonical slices
 * the spec enumerates plus a sanity check that audit logging fires when an
 * admin impersonates another user.
 */
class TargetUserResolverTest extends TestCase
{
    private function makeUser(string $login): User
    {
        $user = new User();
        $user->setLogin($login);
        $user->setPassword('hash');

        return $user;
    }

    /** Set the `id` on a User (normally assigned by the constructor). Used to pin a known UUID for lookup. */
    private function overrideUserId(User $user, Uuid $id): void
    {
        $ref = new ReflectionClass(User::class);
        $prop = $ref->getProperty('id');
        $prop->setValue($user, $id);
    }

    public function testTesterResolvesToSelfWhenNoOverride(): void
    {
        $current = $this->makeUser('tester@example.com');

        $security = $this->createMock(Security::class);
        $security->method('isGranted')
            ->willReturnCallback(static function (string $attr) {
                return $attr === TesterSelfVoter::ATTRIBUTE;
            });

        $resolver = new TargetUserResolver(
            $security,
            $this->createMock(UserRepository::class),
            $this->createMock(AdminActionLogService::class),
        );

        $request = new Request();
        self::assertSame($current, $resolver->resolve($request, $current));
    }

    public function testTesterWithAsUserIdIsDenied(): void
    {
        $current = $this->makeUser('tester@example.com');

        $security = $this->createMock(Security::class);
        $security->method('isGranted')
            ->willReturnCallback(static function (string $attr) {
                // Tester is allowed on self, but NOT ADMIN_CAN_MUTATE_ANY.
                return $attr === TesterSelfVoter::ATTRIBUTE;
            });

        $resolver = new TargetUserResolver(
            $security,
            $this->createMock(UserRepository::class),
            $this->createMock(AdminActionLogService::class),
        );

        $request = new Request(['asUserId' => Uuid::v4()->toRfc4122(), 'reason' => 'bug_repro']);

        $this->expectException(AccessDeniedHttpException::class);
        $resolver->resolve($request, $current);
    }

    public function testAdminWithValidAsUserIdAndReasonIsResolved(): void
    {
        $current = $this->makeUser('admin@example.com');

        $targetId = Uuid::v4();
        $target = $this->makeUser('target@example.com');
        $this->overrideUserId($target, $targetId);

        $security = $this->createMock(Security::class);
        $security->method('isGranted')
            ->willReturnCallback(static function (string $attr) {
                return in_array($attr, [AdminAnyVoter::ATTRIBUTE, TesterSelfVoter::ATTRIBUTE], true);
            });

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects(self::once())
            ->method('find')
            ->with(self::callback(static fn ($id) => $id instanceof Uuid && $id->equals($targetId)))
            ->willReturn($target);

        $auditLogger = $this->createMock(AdminActionLogService::class);
        $auditLogger->expects(self::once())
            ->method('record')
            ->with($current, $target, 'target_user_resolve');

        $resolver = new TargetUserResolver($security, $userRepository, $auditLogger);

        $request = new Request([
            'asUserId' => $targetId->toRfc4122(),
            'reason' => 'bug_repro',
        ]);

        self::assertSame($target, $resolver->resolve($request, $current));
    }

    public function testAdminWithAsUserIdMissingReasonThrows422(): void
    {
        $current = $this->makeUser('admin@example.com');

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(true);

        $resolver = new TargetUserResolver(
            $security,
            $this->createMock(UserRepository::class),
            $this->createMock(AdminActionLogService::class),
        );

        $request = new Request(['asUserId' => Uuid::v4()->toRfc4122()]);

        try {
            $resolver->resolve($request, $current);
            self::fail('Expected HttpException for missing reason.');
        } catch (HttpException $e) {
            self::assertSame(422, $e->getStatusCode());
        }
    }

    public function testAdminWithAsUserIdUnknownUserThrows404(): void
    {
        $current = $this->makeUser('admin@example.com');

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(true);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->method('find')->willReturn(null);

        $resolver = new TargetUserResolver(
            $security,
            $userRepository,
            $this->createMock(AdminActionLogService::class),
        );

        $request = new Request([
            'asUserId' => Uuid::v4()->toRfc4122(),
            'reason' => 'bug_repro',
        ]);

        $this->expectException(NotFoundHttpException::class);
        $resolver->resolve($request, $current);
    }

    public function testAdminWithAsUserIdInvalidReasonThrows422(): void
    {
        $current = $this->makeUser('admin@example.com');

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(true);

        $resolver = new TargetUserResolver(
            $security,
            $this->createMock(UserRepository::class),
            $this->createMock(AdminActionLogService::class),
        );

        $request = new Request([
            'asUserId' => Uuid::v4()->toRfc4122(),
            'reason' => 'definitely_not_a_real_reason',
        ]);

        try {
            $resolver->resolve($request, $current);
            self::fail('Expected HttpException for invalid reason.');
        } catch (HttpException $e) {
            self::assertSame(422, $e->getStatusCode());
        }
    }
}
