<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Mob\Entity\Mob;
use App\Domain\Mob\Enum\MobArchetype;
use App\Domain\Mob\Enum\MobBehavior;
use App\Domain\Mob\Enum\MobClassTier;
use App\Domain\Shared\Enum\Realm;
use App\Infrastructure\Mob\Repository\MobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

/**
 * Console command to seed mob definitions from YAML per-realm files.
 *
 * Reads every `data/mobs/*.yaml` file, upserts mobs by slug using the
 * bestiary schema (`slug`, `name_ua`, `name_en`, `rarity`, `realm`,
 * `class_tier`, `behavior`, `archetype`, `visual_keywords`,
 * `accepts_champion`, `flavor_ua`, `flavor_en`, optional `level_range`).
 *
 * `level_range: [min, max, step]` expands into multiple DB rows with the
 * slug `"{base_slug}_lvl{N}"` so existing `findBySlug` lookups stay stable.
 * HP/XP use the formulas from BUSINESS_LOGIC §10 — UNCHANGED.
 *
 * Replaces `app:import-mobs` (CSV), which is deprecated but retained
 * for one release as a read-only fallback.
 *
 * Usage: php bin/console app:seed-mobs [--realm=olympus] [--dry-run]
 */
#[AsCommand(
    name: 'app:seed-mobs',
    description: 'Seed mob definitions from data/mobs/*.yaml (bestiary format)',
)]
class SeedMobsCommand extends Command
{
    private const BATCH_SIZE = 50;

    public function __construct(
        private readonly MobRepository $mobRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('realm', null, InputOption::VALUE_REQUIRED, 'Seed a single realm (e.g. olympus). Default: all realms.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate YAML without writing to the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $realmFilter = $input->getOption('realm');
        $dryRun = (bool) $input->getOption('dry-run');

        $dir = dirname(__DIR__, 2) . '/data/mobs';
        if (!is_dir($dir)) {
            $io->error(sprintf('Mob seed directory does not exist: %s', $dir));

            return Command::FAILURE;
        }

        $files = glob($dir . '/*.yaml') ?: [];
        if ($files === []) {
            $io->warning('No YAML files found in data/mobs/.');

            return Command::SUCCESS;
        }

        $totalCreated = 0;
        $totalUpdated = 0;
        $processed = 0;

        foreach ($files as $file) {
            $realmSlug = basename($file, '.yaml');
            if ($realmFilter !== null && $realmFilter !== $realmSlug) {
                continue;
            }

            $io->section(sprintf('Seeding realm: %s (%s)', $realmSlug, basename($file)));

            $data = Yaml::parseFile($file);
            if (!is_array($data) || !isset($data['mobs']) || !is_array($data['mobs'])) {
                $io->warning(sprintf('Skipping %s: expected top-level "mobs:" array.', $file));
                continue;
            }

            foreach ($data['mobs'] as $entry) {
                try {
                    [$created, $updated] = $this->processEntry($entry, $dryRun, $io);
                } catch (\Throwable $e) {
                    $io->error(sprintf('Failed on %s: %s', $entry['slug'] ?? '?', $e->getMessage()));
                    continue;
                }

                $totalCreated += $created;
                $totalUpdated += $updated;

                if (!$dryRun && (++$processed % self::BATCH_SIZE === 0)) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->success(sprintf(
            'Seed complete. Created: %d, updated: %d. (dry-run=%s)',
            $totalCreated,
            $totalUpdated,
            $dryRun ? 'yes' : 'no',
        ));

        return Command::SUCCESS;
    }

    /**
     * Process a single YAML mob entry. Expands `level_range` into multiple DB
     * rows; each row gets slug `"{base_slug}_lvl{N}"` to preserve uniqueness.
     *
     * @param array<string, mixed> $entry
     *
     * @return array{0:int,1:int} [createdCount, updatedCount]
     */
    private function processEntry(array $entry, bool $dryRun, SymfonyStyle $io): array
    {
        $baseSlug = (string) ($entry['slug'] ?? '');
        if ($baseSlug === '') {
            throw new \InvalidArgumentException('mob entry missing slug');
        }

        $nameEn = (string) ($entry['name_en'] ?? $entry['name'] ?? $baseSlug);
        $rarity = ItemRarity::from((string) ($entry['rarity'] ?? 'common'));
        $realm = Realm::from((string) ($entry['realm'] ?? 'neutral'));
        $classTier = MobClassTier::from((string) ($entry['class_tier'] ?? 'I'));
        $behavior = MobBehavior::from((string) ($entry['behavior'] ?? 'physical'));
        $archetype = MobArchetype::from((string) ($entry['archetype'] ?? 'beast'));
        $visualKeywords = array_values(array_map('strval', (array) ($entry['visual_keywords'] ?? [])));
        $acceptsChampion = (bool) ($entry['accepts_champion'] ?? true);
        $description = (string) ($entry['flavor_en'] ?? $entry['flavor_ua'] ?? '');

        $levelRange = $this->resolveLevelRange($entry['level_range'] ?? null);

        $created = 0;
        $updated = 0;

        foreach ($levelRange as $level) {
            $levelSlug = sprintf('%s_lvl%d', $baseSlug, $level);
            $existing = $this->mobRepository->findBySlug($levelSlug);
            $mob = $existing ?? new Mob();

            $hp = $this->calculateHp($level);
            $xp = $this->calculateXp($level, $rarity);

            $mob->setName($nameEn)
                ->setSlug($levelSlug)
                ->setLevel($level)
                ->setHp($hp)
                ->setXpReward($xp)
                ->setDescription($description)
                ->setRarity($rarity)
                ->setRealm($realm)
                ->setClassTier($classTier)
                ->setBehavior($behavior)
                ->setArchetype($archetype)
                ->setVisualKeywords($visualKeywords)
                ->setAcceptsChampion($acceptsChampion);

            if ($dryRun) {
                $io->text(sprintf('  [dry-run] %s lvl %d', $levelSlug, $level));
            } elseif ($existing === null) {
                $this->entityManager->persist($mob);
                $created++;
            } else {
                $updated++;
            }
        }

        return [$created, $updated];
    }

    /**
     * Resolve `level_range: [min, max, step]` (or an int or a single int array)
     * into a concrete list of levels. Defaults to a single level of 1.
     *
     * @param mixed $raw
     *
     * @return list<int>
     */
    private function resolveLevelRange(mixed $raw): array
    {
        if ($raw === null) {
            return [1];
        }

        if (is_int($raw)) {
            return [max(1, $raw)];
        }

        if (!is_array($raw)) {
            return [1];
        }

        $min = (int) ($raw[0] ?? 1);
        $max = (int) ($raw[1] ?? $min);
        $step = (int) ($raw[2] ?? 1);
        $step = max(1, $step);
        $min = max(1, $min);
        $max = max($min, $max);

        $levels = [];
        for ($l = $min; $l <= $max; $l += $step) {
            $levels[] = $l;
        }

        return $levels !== [] ? $levels : [$min];
    }

    /**
     * HP formula per BUSINESS_LOGIC §10: base_hp = 20 * level^1.5 + 40.
     * Seed uses the deterministic midpoint (no random jitter so re-seeding is idempotent).
     */
    private function calculateHp(int $level): int
    {
        return (int) round(20.0 * $level ** 1.5 + 40.0);
    }

    /**
     * XP formula per BUSINESS_LOGIC §10, anchored to level & rarity multiplier.
     * No jitter (deterministic seed).
     */
    private function calculateXp(int $level, ItemRarity $rarity): int
    {
        $xpForLevel = 4.2 * $level ** 2 + 28.0 * $level;
        $multiplier = match ($rarity) {
            ItemRarity::Common => 1.0,
            ItemRarity::Uncommon => 1.3,
            ItemRarity::Rare => 1.6,
            ItemRarity::Epic => 2.0,
            ItemRarity::Legendary => 3.0,
        };

        return (int) round(($xpForLevel / 15.0) * $multiplier);
    }
}
