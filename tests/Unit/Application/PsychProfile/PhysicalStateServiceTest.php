<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\PsychProfile;

use App\Application\PsychProfile\Service\PhysicalStateService;
use App\Domain\PsychProfile\Entity\PhysicalStateAnswer;
use App\Domain\User\Entity\User;
use App\Infrastructure\PsychProfile\Repository\PhysicalStateAnswerRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Covers the three callable paths of PhysicalStateService:
 *   1. record() persists and clamps the RPE score.
 *   2. getLatestInWindow() delegates to the repository.
 *   3. getLatest() delegates to the repository.
 */
final class PhysicalStateServiceTest extends TestCase
{
    private function makeUser(): User
    {
        $user = new User();
        $user->setLogin('hero@example.com');
        $user->setPassword('hash');

        return $user;
    }

    public function testRecordPersistsAnswerAndClampsRpeScore(): void
    {
        $user = $this->makeUser();

        $repo = $this->createMock(PhysicalStateAnswerRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')
            ->with(self::isInstanceOf(PhysicalStateAnswer::class));
        $em->expects(self::once())->method('flush');

        $service = new PhysicalStateService($repo, $em, new NullLogger());

        $answer = $service->record($user, null, 9); // clamps to 5

        self::assertSame(5, $answer->getRpeScore());
        self::assertSame($user, $answer->getUser());
        self::assertNull($answer->getWorkoutSession());
    }

    public function testGetLatestInWindowDelegates(): void
    {
        $user = $this->makeUser();
        $expected = new PhysicalStateAnswer();

        $repo = $this->createMock(PhysicalStateAnswerRepository::class);
        $repo->expects(self::once())
            ->method('findLatestForUserWithin')
            ->with($user, 2)
            ->willReturn($expected);

        $em = $this->createMock(EntityManagerInterface::class);
        $service = new PhysicalStateService($repo, $em, new NullLogger());

        self::assertSame($expected, $service->getLatestInWindow($user));
    }

    public function testGetLatestDelegates(): void
    {
        $user = $this->makeUser();
        $expected = new PhysicalStateAnswer();

        $repo = $this->createMock(PhysicalStateAnswerRepository::class);
        $repo->expects(self::once())
            ->method('findLatestForUser')
            ->with($user)
            ->willReturn($expected);

        $em = $this->createMock(EntityManagerInterface::class);
        $service = new PhysicalStateService($repo, $em, new NullLogger());

        self::assertSame($expected, $service->getLatest($user));
    }
}
