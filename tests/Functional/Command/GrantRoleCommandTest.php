<?php

declare(strict_types=1);

namespace App\Tests\Functional\Command;

use App\Domain\User\Entity\User;
use App\Domain\User\Enum\UserRole;
use App\Tests\Functional\AbstractFunctionalTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for {@see \App\Command\GrantRoleCommand}.
 */
class GrantRoleCommandTest extends AbstractFunctionalTest
{
    private CommandTester $commandTester;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = self::getContainer()->get('doctrine')->getManager();

        $application = new Application(self::$kernel);
        $command = $application->find('app:grant-role');
        $this->commandTester = new CommandTester($command);
    }

    private function createUser(string $email): User
    {
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setLogin($email);
        $user->setPassword($hasher->hashPassword($user, 'Password123'));
        $user->setOnboardingCompleted(false);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function testHappyPathGrantsRole(): void
    {
        $this->createUser('jane@example.com');

        $status = $this->commandTester->execute([
            'email' => 'jane@example.com',
            'role' => UserRole::TESTER->value,
        ]);

        self::assertSame(0, $status);

        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(['login' => 'jane@example.com']);

        self::assertInstanceOf(User::class, $user);
        self::assertContains(UserRole::TESTER->value, $user->getRoles());
        self::assertContains(UserRole::USER->value, $user->getRoles());
    }

    public function testProdBlockWithoutForce(): void
    {
        $this->createUser('prod@example.com');

        // Temporarily flip APP_ENV — captured by the command through $_SERVER.
        $originalEnv = $_SERVER['APP_ENV'] ?? 'test';
        $_SERVER['APP_ENV'] = 'prod';

        try {
            $status = $this->commandTester->execute([
                'email' => 'prod@example.com',
                'role' => UserRole::ADMIN->value,
            ]);

            self::assertSame(1, $status);
            self::assertStringContainsString('Refusing to run in production', $this->commandTester->getDisplay());
        } finally {
            $_SERVER['APP_ENV'] = $originalEnv;
        }
    }

    public function testUnknownRoleIsRejected(): void
    {
        $this->createUser('bad@example.com');

        $status = $this->commandTester->execute([
            'email' => 'bad@example.com',
            'role' => 'ROLE_NONEXISTENT',
        ]);

        self::assertSame(1, $status);
        self::assertStringContainsString('Unknown role', $this->commandTester->getDisplay());
    }

    public function testUnknownUserIsRejected(): void
    {
        $status = $this->commandTester->execute([
            'email' => 'ghost@example.com',
            'role' => UserRole::TESTER->value,
        ]);

        self::assertSame(1, $status);
        self::assertStringContainsString('No user found', $this->commandTester->getDisplay());
    }
}
