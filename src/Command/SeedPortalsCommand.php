<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Portal\Entity\Portal;
use App\Domain\Portal\Enum\PortalType;
use App\Domain\Shared\Enum\Realm;
use App\Infrastructure\Portal\Repository\PortalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Seed static portals from data/portals/static.yaml (curated landmarks).
 *
 * Idempotent: upserts by slug. Only manages portals of type=static — dynamic
 * and user_created portals are handled at runtime by PortalSpawnService and
 * are never touched by this command.
 *
 * Usage: php bin/console app:seed-portals [--dry-run]
 */
#[AsCommand(
    name: 'app:seed-portals',
    description: 'Seed curated static portals from data/portals/static.yaml',
)]
class SeedPortalsCommand extends Command
{
    public function __construct(
        private readonly PortalRepository $portalRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate YAML without writing to the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $path = dirname(__DIR__, 2) . '/data/portals/static.yaml';
        if (!is_file($path)) {
            $io->error(sprintf('Static portals file not found: %s', $path));

            return Command::FAILURE;
        }

        $data = Yaml::parseFile($path);
        if (!is_array($data) || !isset($data['portals']) || !is_array($data['portals'])) {
            $io->error('Expected top-level "portals:" array.');

            return Command::FAILURE;
        }

        $created = 0;
        $updated = 0;

        foreach ($data['portals'] as $row) {
            try {
                $effect = $this->upsertPortal($row, $dryRun);
            } catch (\Throwable $e) {
                $io->error(sprintf('Failed on %s: %s', $row['slug'] ?? '?', $e->getMessage()));
                continue;
            }

            if ($effect === 'created') {
                $created++;
            } elseif ($effect === 'updated') {
                $updated++;
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->success(sprintf('Portals seeded. Created: %d, updated: %d (dry-run=%s)', $created, $updated, $dryRun ? 'yes' : 'no'));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function upsertPortal(array $row, bool $dryRun): string
    {
        $slug = (string) ($row['slug'] ?? '');
        if ($slug === '') {
            throw new \InvalidArgumentException('missing slug');
        }

        $existing = $this->portalRepository->findBySlug($slug);
        $portal = $existing ?? new Portal();

        $portal->setName((string) ($row['name'] ?? $slug))
            ->setSlug($slug)
            ->setType(PortalType::Static)
            ->setRealm(Realm::from((string) ($row['realm'] ?? 'neutral')))
            ->setLatitude((float) ($row['latitude'] ?? 0))
            ->setLongitude((float) ($row['longitude'] ?? 0))
            ->setRadiusM((int) ($row['radius_m'] ?? 100))
            ->setTier((int) ($row['tier'] ?? 1))
            ->setChallengeType(isset($row['challenge_type']) ? (string) $row['challenge_type'] : null)
            ->setChallengeParams(is_array($row['challenge_params'] ?? null) ? $row['challenge_params'] : [])
            ->setRewardArtifactSlug(isset($row['reward_artifact_slug']) ? (string) $row['reward_artifact_slug'] : null);

        if ($dryRun) {
            return $existing === null ? 'created' : 'updated';
        }

        if ($existing === null) {
            $this->entityManager->persist($portal);

            return 'created';
        }

        return 'updated';
    }
}
