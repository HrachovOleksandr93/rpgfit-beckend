<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Workout\Entity\Exercise;
use App\Domain\Workout\Entity\SplitTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Functional tests for the app:seed-exercises console command.
 *
 * Boots the Symfony kernel, recreates the database schema in SQLite,
 * then tests exercise and split template seeding with correct counts.
 */
class SeedExercisesCommandTest extends AbstractFunctionalTest
{
    private CommandTester $commandTester;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = self::getContainer()->get('doctrine')->getManager();

        $application = new Application(self::$kernel);
        $command = $application->find('app:seed-exercises');
        $this->commandTester = new CommandTester($command);
    }

    /** Verify the seeder creates exactly 216 exercises (108 gym + 108 activity-based). */
    public function testSeederCreatesCorrectExerciseCount(): void
    {
        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $exercises = $this->em->getRepository(Exercise::class)->findAll();
        $this->assertCount(216, $exercises);
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
        $this->assertCount(216, $exercises);

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

    /** Verify exercises have correct muscle group distribution (gym + activity-based). */
    public function testExerciseMuscleGroupDistribution(): void
    {
        $this->commandTester->execute([]);

        // 14 gym + 4 activity (Jab-Cross Combo, Breaststroke Laps, Burpees, Surf Pop-Up Drill) = 18
        $chestExercises = $this->em->getRepository(Exercise::class)->findBy(['primaryMuscle' => 'chest']);
        $this->assertCount(18, $chestExercises);

        // 14 gym + 15 activity = 29
        $backExercises = $this->em->getRepository(Exercise::class)->findBy(['primaryMuscle' => 'back']);
        $this->assertCount(29, $backExercises);

        // 12 gym + 17 activity = 29
        $coreExercises = $this->em->getRepository(Exercise::class)->findBy(['primaryMuscle' => 'core']);
        $this->assertCount(29, $coreExercises);
    }
}
