<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Activity\Entity\ProfessionSkill;
use App\Domain\Skill\Entity\Skill;
use App\Domain\Skill\Entity\SkillStatBonus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Functional tests for the app:seed-skills console command.
 *
 * Boots the Symfony kernel, recreates the database schema in SQLite,
 * seeds professions first (dependency), then tests skill seeding with correct counts.
 */
class SeedSkillsCommandTest extends AbstractFunctionalTest
{
    private CommandTester $commandTester;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = self::getContainer()->get('doctrine')->getManager();

        $application = new Application(self::$kernel);

        // Seed professions first (skills depend on them for links)
        $professionCommand = $application->find('app:seed-professions');
        $professionTester = new CommandTester($professionCommand);
        $professionTester->execute([]);

        $command = $application->find('app:seed-skills');
        $this->commandTester = new CommandTester($command);
    }

    /** Verify the seeder creates exactly 34 unique skills (2 universal + 32 profession; race skills removed 2026-04-18). */
    public function testSeederCreates34Skills(): void
    {
        $this->commandTester->execute([]);

        $this->assertSame(0, $this->commandTester->getStatusCode());

        $skills = $this->em->getRepository(Skill::class)->findAll();
        $this->assertCount(34, $skills);
    }

    /** Verify no race-flagged skills are seeded anymore. */
    public function testSeederCreatesNoRaceSkills(): void
    {
        $this->commandTester->execute([]);

        $raceSkills = $this->em->getRepository(Skill::class)->findBy(['isRaceSkill' => true]);
        $this->assertCount(0, $raceSkills);
    }

    /** Verify the seeder creates exactly 2 universal active skills. */
    public function testSeederCreates2UniversalSkills(): void
    {
        $this->commandTester->execute([]);

        $universalSkills = $this->em->getRepository(Skill::class)->findBy(['isUniversal' => true]);
        $this->assertCount(2, $universalSkills);
    }

    /** Verify the seeder creates profession-skill links. */
    public function testSeederCreatesProfessionSkillLinks(): void
    {
        $this->commandTester->execute([]);

        $links = $this->em->getRepository(ProfessionSkill::class)->findAll();
        // 48 professions: 16 T1 (3 skills each) + 16 T2 (4 skills each) + 16 T3 (3 skills each) = 48+64+48 = 160
        $this->assertCount(160, $links);
    }

    /** Verify the seeder creates stat bonuses for skills. */
    public function testSeederCreatesStatBonuses(): void
    {
        $this->commandTester->execute([]);

        $bonuses = $this->em->getRepository(SkillStatBonus::class)->findAll();
        $this->assertGreaterThan(34, count($bonuses));
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

        $skills = $this->em->getRepository(Skill::class)->findAll();
        $this->assertCount(34, $skills);

        $links = $this->em->getRepository(ProfessionSkill::class)->findAll();
        $this->assertCount(160, $links);
    }

    /** Verify that running the seeder twice produces the same counts (idempotent). */
    public function testIdempotentRunTwiceSameCounts(): void
    {
        // First run
        $this->commandTester->execute([]);
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $skillsFirst = count($this->em->getRepository(Skill::class)->findAll());
        $linksFirst = count($this->em->getRepository(ProfessionSkill::class)->findAll());

        // Clear entity manager cache for fresh reads
        $this->em->clear();

        // Second run (should skip all existing)
        $this->commandTester->execute([]);
        $this->assertSame(0, $this->commandTester->getStatusCode());

        $skillsSecond = count($this->em->getRepository(Skill::class)->findAll());
        $linksSecond = count($this->em->getRepository(ProfessionSkill::class)->findAll());

        $this->assertSame($skillsFirst, $skillsSecond);
        $this->assertSame($linksFirst, $linksSecond);
    }
}
