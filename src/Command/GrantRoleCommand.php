<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\User\Enum\UserRole;
use App\Infrastructure\User\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Grant a `UserRole` to an existing user identified by email.
 *
 * Usage:
 *   php bin/console app:grant-role jane@example.com ROLE_TESTER
 *
 * Refuses to run when `APP_ENV=prod` unless `--force-prod` is passed.
 * This guard protects production from accidental role escalations run
 * out of an over-privileged deploy shell.
 */
#[AsCommand(
    name: 'app:grant-role',
    description: 'Grant a UserRole (ROLE_USER|ROLE_TESTER|ROLE_ADMIN|ROLE_SUPERADMIN) to a user by email',
)]
class GrantRoleCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email (login) of the user to promote')
            ->addArgument('role', InputArgument::REQUIRED, 'Role value, e.g. ROLE_TESTER')
            ->addOption('force-prod', null, InputOption::VALUE_NONE, 'Allow running when APP_ENV=prod');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $env = (string) ($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev');
        $forceProd = (bool) $input->getOption('force-prod');
        if ($env === 'prod' && !$forceProd) {
            $io->error('Refusing to run in production without --force-prod. Audit trail is mandatory.');

            return Command::FAILURE;
        }

        $email = (string) $input->getArgument('email');
        $rawRole = (string) $input->getArgument('role');

        $role = UserRole::tryFrom($rawRole);
        if ($role === null) {
            $allowed = implode(', ', array_map(static fn (UserRole $r) => $r->value, UserRole::cases()));
            $io->error(sprintf('Unknown role "%s". Allowed: %s', $rawRole, $allowed));

            return Command::FAILURE;
        }

        $user = $this->userRepository->findByLogin($email);
        if ($user === null) {
            $io->error(sprintf('No user found with login "%s".', $email));

            return Command::FAILURE;
        }

        if ($user->hasRole($role)) {
            $io->warning(sprintf('User %s already has role %s — no change.', $email, $role->value));

            return Command::SUCCESS;
        }

        $user->addRole($role);
        $this->entityManager->flush();

        $io->success(sprintf('Granted %s to %s. Current roles: %s',
            $role->value,
            $email,
            implode(', ', $user->getRoles()),
        ));

        return Command::SUCCESS;
    }
}
