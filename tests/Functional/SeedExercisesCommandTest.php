<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Workout\Entity\Exercise;
use App\Domain\Workout\Entity\SplitTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Functional tests for the app:seed-exercises console command.
 *
 * Boots the Symfony kernel, recreates the database schema in SQLite,
 * then tests exercise and split template seeding with correct counts.
 */
class SeedExercisesCommandTest extends KernelTestCase
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
        $command = $application->find('app:seed-exercises');
        $this->commandTester = new CommandTester($command);
    }

    /** Verify the seeder creates exactly 108 exercises (shared exercises counted once under primary muscle). */
    public function testSeederCreatesCorrectExerciseCount(): void
    {
        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $exercises = $this->em->getRepository(Exercise::class)->findAll();
        $this->assertCount(108, $exercises);
    }

    /** Verify the seeder creates exactly 6 split templates. */
    public function testSeederCreatesCorrectTemplateCount(): void
    {
        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $templates = $this->em->getRepository(SplitTemplate::class)->findAll();
        $this->assertCount(6, $templates);
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

        $exercises = $this->em->getRepository(Exercise::class)->findAll();
        $this->assertCount(108, $exercises);

        $templates = $this->em->getRepository(SplitTemplate::class)->findAll();
        $this->assertCount(6, $templates);
    }

    /** Verify that running the seeder twice produces the same counts (idempotent). */
    public function testIdempotentRunTwiceSameCounts(): void
    {
        // First run
        $this->commandTester->execute([]);
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $exercisesFirst = count($this->em->getRepository(Exercise::class)->findAll());
        $templatesFirst = count($this->em->getRepository(SplitTemplate::class)->findAll());

        // Clear entity manager cache for fresh reads
        $this->em->clear();

        // Second run (should skip all existing)
        $this->commandTester->execute([]);
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $exercisesSecond = count($this->em->getRepository(Exercise::class)->findAll());
        $templatesSecond = count($this->em->getRepository(SplitTemplate::class)->findAll());

        $this->assertSame($exercisesFirst, $exercisesSecond);
        $this->assertSame($templatesFirst, $templatesSecond);
    }

    /** Verify exercises have correct muscle group distribution. */
    public function testExerciseMuscleGroupDistribution(): void
    {
        $this->commandTester->execute([]);

        $chestExercises = $this->em->getRepository(Exercise::class)->findBy(['primaryMuscle' => 'chest']);
        $this->assertCount(14, $chestExercises);

        $backExercises = $this->em->getRepository(Exercise::class)->findBy(['primaryMuscle' => 'back']);
        $this->assertCount(14, $backExercises);

        $coreExercises = $this->em->getRepository(Exercise::class)->findBy(['primaryMuscle' => 'core']);
        $this->assertCount(12, $coreExercises);
    }
}
