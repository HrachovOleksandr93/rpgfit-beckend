<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Mob\Entity\Mob;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Functional tests for the app:import-mobs console command.
 *
 * Boots the Symfony kernel, recreates the database schema in SQLite,
 * and tests CSV import, --dry-run, --update, invalid data, and missing file scenarios.
 */
class ImportMobsCommandTest extends AbstractFunctionalTest
{
    private CommandTester $commandTester;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = self::getContainer()->get('doctrine')->getManager();

        $application = new Application(self::$kernel);
        $command = $application->find('app:import-mobs');
        $this->commandTester = new CommandTester($command);
    }

    /** Verify that importing a valid CSV creates mob entities in the database. */
    public function testImportCreatesNewMobs(): void
    {
        $this->commandTester->execute([
            'file' => $this->getFixturePath(),
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Created: 5', $output);

        $mobs = $this->em->getRepository(Mob::class)->findAll();
        $this->assertCount(5, $mobs);
    }

    /** Verify that --dry-run validates CSV rows without persisting any entities. */
    public function testDryRunDoesNotPersist(): void
    {
        $this->commandTester->execute([
            'file' => $this->getFixturePath(),
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Created: 5', $output);
        $this->assertStringContainsString('Dry-run mode', $output);

        // No mobs should actually be persisted
        $mobs = $this->em->getRepository(Mob::class)->findAll();
        $this->assertCount(0, $mobs);
    }

    /** Verify that --update overwrites existing mobs matched by slug. */
    public function testUpdateOverwritesExistingMobs(): void
    {
        // First import to create mobs
        $this->commandTester->execute([
            'file' => $this->getFixturePath(),
        ]);
        $this->assertSame(0, $this->commandTester->getStatusCode());

        // Clear the entity manager to ensure fresh reads
        $this->em->clear();

        // Second import with --update to overwrite
        $this->commandTester->execute([
            'file' => $this->getFixturePath(),
            '--update' => true,
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Updated: 5', $output);
        $this->assertStringContainsString('Created: 0', $output);

        // Should still only have 5 mobs total (updated, not duplicated)
        $mobs = $this->em->getRepository(Mob::class)->findAll();
        $this->assertCount(5, $mobs);
    }

    /** Verify that re-importing without --update skips existing mobs. */
    public function testSkipsExistingWithoutUpdate(): void
    {
        // First import
        $this->commandTester->execute([
            'file' => $this->getFixturePath(),
        ]);

        $this->em->clear();

        // Second import without --update should skip all
        $this->commandTester->execute([
            'file' => $this->getFixturePath(),
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Skipped: 5', $output);
    }

    /** Verify that invalid CSV data produces error messages. */
    public function testInvalidCsvShowsErrors(): void
    {
        // Create a temporary CSV with invalid data
        $tmpFile = tempnam(sys_get_temp_dir(), 'mob_test_');
        file_put_contents($tmpFile, implode("\n", [
            'name,slug,level,hp,xp_reward,rarity,description',
            'Bad Mob,bad-mob,0,100,10,common,Level zero is invalid',
            'Another Bad,,5,100,10,common,Missing slug',
            'HP Zero,hp-zero,5,0,10,common,Zero HP is invalid',
        ]));

        try {
            $this->commandTester->execute([
                'file' => $tmpFile,
            ]);

            $this->assertSame(0, $this->commandTester->getStatusCode());

            $output = $this->commandTester->getDisplay();
            $this->assertStringContainsString('Errors: 3', $output);
        } finally {
            unlink($tmpFile);
        }
    }

    /** Verify that a non-existent file path produces an error. */
    public function testMissingFileShowsError(): void
    {
        $this->commandTester->execute([
            'file' => '/non/existent/file.csv',
        ]);

        $this->assertSame(1, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('File not found', $output);
    }

    /** Return the absolute path to the test fixtures CSV file. */
    private function getFixturePath(): string
    {
        return dirname(__DIR__) . '/fixtures/mobs_test.csv';
    }
}
