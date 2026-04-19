<?php

declare(strict_types=1);

namespace App\Command;

use App\Application\Test\Service\TestHarnessGate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Flip the test-harness `GameSetting` override off once the configured TTL
 * elapses (founder decision Q7 — 60 min default).
 *
 * Intended to be run from cron every minute or so. Idempotent:
 *   - If no override is active, exits 0 with "nothing to do".
 *   - If the override is active but not yet expired, exits 0 with the
 *     remaining time.
 *   - If the override has expired, flips it off and exits 0.
 *
 * Recommended cron entry (docker-compose + Symfony):
 *   `* * * * * php /app/bin/console app:testing-check`
 */
#[AsCommand(
    name: 'app:testing-check',
    description: 'Auto-revert the test-harness runtime override when its TTL expires',
)]
final class TestingCheckCommand extends Command
{
    public function __construct(
        private readonly TestHarnessGate $gate,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $expiresAt = $this->gate->getSettingExpiresAt();
        if ($expiresAt === null) {
            $io->writeln('[info] No active runtime override — nothing to do.');

            return Command::SUCCESS;
        }

        $now = new \DateTimeImmutable();
        if ($expiresAt > $now) {
            $io->writeln(sprintf(
                '[info] Runtime override active. Expires at %s (in %d minutes).',
                $expiresAt->format(\DateTimeInterface::ATOM),
                (int) ceil(($expiresAt->getTimestamp() - $now->getTimestamp()) / 60),
            ));

            return Command::SUCCESS;
        }

        $reverted = $this->gate->revertIfExpired();
        if ($reverted) {
            $io->success('Test-harness override expired — automatically disabled.');
        } else {
            $io->writeln('[info] Nothing to revert.');
        }

        return Command::SUCCESS;
    }
}
