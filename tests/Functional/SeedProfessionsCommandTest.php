<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Activity\Entity\ActivityCategory;
use App\Domain\Activity\Entity\ActivityType;
use App\Domain\Activity\Entity\Profession;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Functional tests for the app:seed-professions console command.
 *
 * Boots the Symfony kernel, recreates the database schema in SQLite,
 * and tests seeding categories, professions, activity types, --clear, and idempotency.
 */
class SeedProfessionsCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->em = self::getContainer()->get('doctrine')->getManager();

        // Recreate schema for a clean database state
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        $application = new Application(self::$kernel);
        $command = $application->find('app:seed-professions');
        $this->commandTester = new CommandTester($command);
    }

    /** Verify that the seeder creates exactly 16 activity categories. */
    public function testSeederCreates16Categories(): void
    {
        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $categories = $this->em->getRepository(ActivityCategory::class)->findAll();
        $this->assertCount(16, $categories);
    }

    /** Verify that the seeder creates exactly 48 professions (3 tiers x 16 categories). */
    public function testSeederCreates48Professions(): void
    {
        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $professions = $this->em->getRepository(Profession::class)->findAll();
        $this->assertCount(48, $professions);
    }

    /** Verify that the seeder creates all activity types (more than 90). */
    public function testSeederCreatesActivityTypes(): void
    {
        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $activityTypes = $this->em->getRepository(ActivityType::class)->findAll();
        $this->assertGreaterThan(90, count($activityTypes));
    }

    /** Verify that --clear deletes existing data and re-seeds cleanly. */
    public function testClearOptionDeletesAndReseeds(): void
    {
        // First seed
        $this->commandTester->execute([]);
        $this->assertSame(0, $this->commandTester->getStatusCode());

        // Clear and re-seed
        $this->em->clear();
        $this->commandTester->execute(['--clear' => true]);
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $categories = $this->em->getRepository(ActivityCategory::class)->findAll();
        $this->assertCount(16, $categories);

        $professions = $this->em->getRepository(Profession::class)->findAll();
        $this->assertCount(48, $professions);

        $activityTypes = $this->em->getRepository(ActivityType::class)->findAll();
        $this->assertGreaterThan(90, count($activityTypes));
    }

    /** Verify that running the seeder twice produces the same counts (idempotent). */
    public function testIdempotentRunTwiceSameCounts(): void
    {
        // First run
        $this->commandTester->execute([]);
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $categoriesFirst = count($this->em->getRepository(ActivityCategory::class)->findAll());
        $professionsFirst = count($this->em->getRepository(Profession::class)->findAll());
        $activityTypesFirst = count($this->em->getRepository(ActivityType::class)->findAll());

        // Clear entity manager cache to ensure fresh reads
        $this->em->clear();

        // Second run (should skip all existing)
        $this->commandTester->execute([]);
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $categoriesSecond = count($this->em->getRepository(ActivityCategory::class)->findAll());
        $professionsSecond = count($this->em->getRepository(Profession::class)->findAll());
        $activityTypesSecond = count($this->em->getRepository(ActivityType::class)->findAll());

        $this->assertSame($categoriesFirst, $categoriesSecond);
        $this->assertSame($professionsFirst, $professionsSecond);
        $this->assertSame($activityTypesFirst, $activityTypesSecond);
    }
}
