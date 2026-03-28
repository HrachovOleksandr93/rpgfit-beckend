<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Mob\Entity\Mob;
use App\Infrastructure\Mob\Repository\MobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to import mob definitions from a CSV file.
 *
 * Reads a CSV with columns: name, slug, level, hp, xp_reward, rarity, description.
 * Supports --update to overwrite existing mobs matched by slug,
 * and --dry-run to validate without persisting to the database.
 * Batch-flushes every 50 entities for memory efficiency.
 *
 * Usage: php bin/console app:import-mobs data/mobs.csv [--update] [--dry-run]
 */
#[AsCommand(
    name: 'app:import-mobs',
    description: 'Import mob definitions from a CSV file',
)]
class ImportMobsCommand extends Command
{
    /** Expected CSV header columns in exact order */
    private const EXPECTED_HEADERS = ['name', 'slug', 'level', 'hp', 'xp_reward', 'rarity', 'description'];

    /** Number of entities to accumulate before flushing to the database */
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
            ->addArgument('file', InputArgument::REQUIRED, 'Path to the CSV file')
            ->addOption('update', null, InputOption::VALUE_NONE, 'Update existing mobs matched by slug instead of skipping')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate CSV without writing to the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');
        $doUpdate = $input->getOption('update');
        $dryRun = $input->getOption('dry-run');

        // Verify the CSV file exists and is readable
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $io->error(sprintf('File not found or not readable: %s', $filePath));

            return Command::FAILURE;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $io->error(sprintf('Could not open file: %s', $filePath));

            return Command::FAILURE;
        }

        $io->info(sprintf('Importing mobs from %s...', $filePath));

        if ($dryRun) {
            $io->note('Dry-run mode: no changes will be persisted.');
        }

        // Read and validate the header row
        $headers = fgetcsv($handle, 0, ',', '"', '');
        if ($headers === false || $headers !== self::EXPECTED_HEADERS) {
            $io->error(sprintf(
                'Invalid CSV header. Expected: %s',
                implode(',', self::EXPECTED_HEADERS)
            ));
            fclose($handle);

            return Command::FAILURE;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $row = 0;
        $batchCount = 0;

        // Process each data row in the CSV
        while (($data = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $row++;

            // Skip empty rows
            if (count($data) < 5) {
                $io->warning(sprintf('Row %d: not enough columns, skipping.', $row));
                $errors++;

                continue;
            }

            $record = array_combine(self::EXPECTED_HEADERS, array_pad($data, count(self::EXPECTED_HEADERS), ''));

            // Validate required fields are not empty
            $validationError = $this->validateRow($record, $row);
            if ($validationError !== null) {
                $io->error($validationError);
                $errors++;

                continue;
            }

            $name = trim($record['name']);
            $slug = trim($record['slug']);
            $level = (int) $record['level'];
            $hp = (int) $record['hp'];
            $xpReward = (int) $record['xp_reward'];
            $rarityValue = trim($record['rarity']);
            $description = trim($record['description']) !== '' ? trim($record['description']) : null;

            // Resolve rarity enum if provided
            $rarity = null;
            if ($rarityValue !== '') {
                $rarity = ItemRarity::tryFrom($rarityValue);
                if ($rarity === null) {
                    $io->error(sprintf('Row %d: invalid rarity "%s".', $row, $rarityValue));
                    $errors++;

                    continue;
                }
            }

            // Check if a mob with this slug already exists
            $existing = $this->mobRepository->findBySlug($slug);

            if ($existing !== null) {
                if ($doUpdate) {
                    // Update existing mob fields
                    $existing->setName($name)
                        ->setLevel($level)
                        ->setHp($hp)
                        ->setXpReward($xpReward)
                        ->setRarity($rarity)
                        ->setDescription($description);

                    if (!$dryRun) {
                        $this->entityManager->persist($existing);
                    }

                    $io->text(sprintf('[OK] Row %d: %s (level %d) — updated', $row, $name, $level));
                    $updated++;
                } else {
                    $io->text(sprintf('[SKIP] Row %d: %s — already exists (use --update to overwrite)', $row, $name));
                    $skipped++;
                }
            } else {
                // Create a new mob entity
                $mob = new Mob();
                $mob->setName($name)
                    ->setSlug($slug)
                    ->setLevel($level)
                    ->setHp($hp)
                    ->setXpReward($xpReward)
                    ->setRarity($rarity)
                    ->setDescription($description);

                if (!$dryRun) {
                    $this->entityManager->persist($mob);
                }

                $io->text(sprintf('[OK] Row %d: %s (level %d) — created', $row, $name, $level));
                $created++;
            }

            $batchCount++;

            // Flush in batches for memory efficiency
            if (!$dryRun && $batchCount % self::BATCH_SIZE === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        fclose($handle);

        // Final flush for any remaining entities
        if (!$dryRun && $batchCount % self::BATCH_SIZE !== 0) {
            $this->entityManager->flush();
        }

        $io->success(sprintf(
            'Done! Created: %d, Updated: %d, Skipped: %d, Errors: %d',
            $created,
            $updated,
            $skipped,
            $errors
        ));

        return Command::SUCCESS;
    }

    /**
     * Validate a single CSV row and return an error message or null if valid.
     */
    private function validateRow(array $record, int $row): ?string
    {
        // Check required fields are not empty
        foreach (['name', 'slug', 'level', 'hp', 'xp_reward'] as $field) {
            if (trim($record[$field]) === '') {
                return sprintf('Row %d: missing required field "%s".', $row, $field);
            }
        }

        $level = (int) $record['level'];
        if ($level < 1 || $level > 100) {
            return sprintf('Row %d: level must be between 1 and 100, got %d.', $row, $level);
        }

        $hp = (int) $record['hp'];
        if ($hp <= 0) {
            return sprintf('Row %d: hp must be greater than 0, got %d.', $row, $hp);
        }

        $xpReward = (int) $record['xp_reward'];
        if ($xpReward <= 0) {
            return sprintf('Row %d: xp_reward must be greater than 0, got %d.', $row, $xpReward);
        }

        return null;
    }
}
