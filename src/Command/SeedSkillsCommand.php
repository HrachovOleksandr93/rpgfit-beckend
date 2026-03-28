<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Activity\Entity\Profession;
use App\Domain\Activity\Entity\ProfessionSkill;
use App\Domain\Character\Enum\StatType;
use App\Domain\Skill\Entity\Skill;
use App\Domain\Skill\Entity\SkillStatBonus;
use App\Domain\User\Enum\CharacterRace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to seed all RPG skills and profession-skill links.
 *
 * Populates the database with 39 unique skills (5 race passives, 2 universal actives,
 * 32 profession skills across 3 tiers) and links them to the 48 professions.
 * Idempotent by default: skips existing slugs. Use --clear to delete and re-seed.
 *
 * Usage: php bin/console app:seed-skills [--clear]
 */
#[AsCommand(
    name: 'app:seed-skills',
    description: 'Seed all RPG skills and profession-skill links from the design document',
)]
class SeedSkillsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Delete existing skill data before seeding');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $clear = $input->getOption('clear');

        if ($clear) {
            $io->warning('Clearing existing skill data...');
            $this->clearExistingData();
            $io->info('Existing skill data cleared.');
        }

        // Step 1: Seed race passive skills
        $skillMap = [];
        $raceCount = $this->seedRaceSkills($io, $skillMap);

        // Step 2: Seed universal active skills
        $universalCount = $this->seedUniversalSkills($io, $skillMap);

        // Step 3: Seed profession skills (T1, T2, T3 passives and actives)
        $professionSkillCount = $this->seedProfessionSkills($io, $skillMap);

        // Step 4: Link skills to professions via ProfessionSkill
        $linkCount = $this->seedProfessionSkillLinks($io, $skillMap);

        $this->entityManager->flush();

        $io->success(sprintf(
            'Seeding complete! Race skills: %d, Universal skills: %d, Profession skills: %d, Profession-skill links: %d',
            $raceCount,
            $universalCount,
            $professionSkillCount,
            $linkCount,
        ));

        return Command::SUCCESS;
    }

    /**
     * Delete all existing skill-related data.
     * Order matters due to foreign key constraints.
     */
    private function clearExistingData(): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('DELETE FROM profession_skills');
        $conn->executeStatement('DELETE FROM skill_stat_bonuses');
        $conn->executeStatement('DELETE FROM user_skills');
        $conn->executeStatement('DELETE FROM skills');
    }

    /**
     * Seed the 5 race passive skills from the design document.
     *
     * @param array<string, Skill> $skillMap Slug-indexed map of created skills
     * @return int Number of skills created
     */
    private function seedRaceSkills(SymfonyStyle $io, array &$skillMap): int
    {
        $raceSkills = [
            [
                'slug' => 'versatile-nature',
                'name' => 'Versatile Nature',
                'race' => CharacterRace::Human,
                'bonuses' => ['str' => 2, 'dex' => 2, 'con' => 2],
                'description' => 'Humans adapt to any challenge. A balanced bonus to all attributes reflects their jack-of-all-trades heritage.',
            ],
            [
                'slug' => 'blood-of-the-horde',
                'name' => 'Blood of the Horde',
                'race' => CharacterRace::Orc,
                'bonuses' => ['str' => 4, 'con' => 1],
                'description' => 'Orcs are born with savage power coursing through their veins. Brute strength is their birthright.',
            ],
            [
                'slug' => 'mountain-born',
                'name' => 'Mountain Born',
                'race' => CharacterRace::Dwarf,
                'bonuses' => ['str' => 2, 'con' => 3],
                'description' => 'Dwarves are carved from the stone of the deep mountains — sturdy, unbreakable, and impossible to move.',
            ],
            [
                'slug' => 'shadow-instinct',
                'name' => 'Shadow Instinct',
                'race' => CharacterRace::DarkElf,
                'bonuses' => ['str' => 1, 'dex' => 4],
                'description' => 'Dark Elves move with lethal quickness honed in the lightless depths of the underworld.',
            ],
            [
                'slug' => 'sylvan-grace',
                'name' => 'Sylvan Grace',
                'race' => CharacterRace::LightElf,
                'bonuses' => ['dex' => 3, 'con' => 2],
                'description' => 'Light Elves carry the flowing grace of the ancient forests — nimble and enduring as the wind through the trees.',
            ],
        ];

        $count = 0;
        foreach ($raceSkills as $data) {
            $existing = $this->entityManager->getRepository(Skill::class)->findOneBy(['slug' => $data['slug']]);
            if ($existing) {
                $skillMap[$data['slug']] = $existing;
                $io->text(sprintf('  Skipping existing race skill: %s', $data['name']));
                continue;
            }

            $skill = new Skill();
            $skill->setName($data['name'])
                ->setSlug($data['slug'])
                ->setDescription($data['description'])
                ->setSkillType('passive')
                ->setIsRaceSkill(true)
                ->setRaceRestriction($data['race']);

            $this->addStatBonuses($skill, $data['bonuses']);

            $this->entityManager->persist($skill);
            $skillMap[$data['slug']] = $skill;
            $count++;
            $io->text(sprintf('  Created race skill: %s', $data['name']));
        }

        return $count;
    }

    /**
     * Seed the 2 universal active skills available to all players.
     *
     * @param array<string, Skill> $skillMap Slug-indexed map of created skills
     * @return int Number of skills created
     */
    private function seedUniversalSkills(SymfonyStyle $io, array &$skillMap): int
    {
        $universalSkills = [
            [
                'slug' => 'second-wind',
                'name' => 'Second Wind',
                'bonuses' => ['con' => 3],
                'duration' => 60,
                'cooldown' => 60,
                'description' => 'A burst of renewed energy that temporarily boosts your endurance. Reliable and always ready when you need a push.',
            ],
            [
                'slug' => 'battle-fury',
                'name' => 'Battle Fury',
                'bonuses' => ['str' => 5, 'dex' => 3],
                'duration' => 30,
                'cooldown' => 240,
                'description' => 'Channel your inner warrior for a devastating burst of power and speed. Use wisely — the fury takes time to rekindle.',
            ],
        ];

        $count = 0;
        foreach ($universalSkills as $data) {
            $existing = $this->entityManager->getRepository(Skill::class)->findOneBy(['slug' => $data['slug']]);
            if ($existing) {
                $skillMap[$data['slug']] = $existing;
                $io->text(sprintf('  Skipping existing universal skill: %s', $data['name']));
                continue;
            }

            $skill = new Skill();
            $skill->setName($data['name'])
                ->setSlug($data['slug'])
                ->setDescription($data['description'])
                ->setSkillType('active')
                ->setIsUniversal(true)
                ->setDuration($data['duration'])
                ->setCooldown($data['cooldown']);

            $this->addStatBonuses($skill, $data['bonuses']);

            $this->entityManager->persist($skill);
            $skillMap[$data['slug']] = $skill;
            $count++;
            $io->text(sprintf('  Created universal skill: %s', $data['name']));
        }

        return $count;
    }

    /**
     * Seed all profession skills (T1, T2, T3 passives and actives) from the design document.
     *
     * @param array<string, Skill> $skillMap Slug-indexed map of created skills
     * @return int Number of skills created
     */
    private function seedProfessionSkills(SymfonyStyle $io, array &$skillMap): int
    {
        $professionSkills = $this->getProfessionSkillDefinitions();

        $count = 0;
        foreach ($professionSkills as $data) {
            $existing = $this->entityManager->getRepository(Skill::class)->findOneBy(['slug' => $data['slug']]);
            if ($existing) {
                $skillMap[$data['slug']] = $existing;
                continue;
            }

            $skill = new Skill();
            $skill->setName($data['name'])
                ->setSlug($data['slug'])
                ->setDescription($data['description'])
                ->setSkillType($data['type'])
                ->setTier($data['tier']);

            if ($data['type'] === 'active') {
                $skill->setDuration($data['duration']);
                $skill->setCooldown($data['cooldown']);
            }

            $this->addStatBonuses($skill, $data['bonuses']);

            $this->entityManager->persist($skill);
            $skillMap[$data['slug']] = $skill;
            $count++;
            $io->text(sprintf('  Created profession skill: %s (T%d %s)', $data['name'], $data['tier'], $data['type']));
        }

        return $count;
    }

    /**
     * Link skills to professions via ProfessionSkill junction entities.
     *
     * @param array<string, Skill> $skillMap Slug-indexed map of created skills
     * @return int Number of links created
     */
    private function seedProfessionSkillLinks(SymfonyStyle $io, array &$skillMap): int
    {
        $mappings = $this->getProfessionSkillMappings();
        $count = 0;

        foreach ($mappings as $mapping) {
            $professionSlug = $mapping['profession_slug'];
            $skillSlugs = $mapping['skill_slugs'];

            $profession = $this->entityManager->getRepository(Profession::class)->findOneBy(['slug' => $professionSlug]);
            if (!$profession) {
                $io->warning(sprintf('Profession not found: %s — skipping links', $professionSlug));
                continue;
            }

            foreach ($skillSlugs as $skillSlug) {
                if (!isset($skillMap[$skillSlug])) {
                    $io->warning(sprintf('Skill not found in map: %s — skipping link for %s', $skillSlug, $professionSlug));
                    continue;
                }

                // Check for existing link to maintain idempotency
                $existingLink = $this->entityManager->getRepository(ProfessionSkill::class)->findOneBy([
                    'profession' => $profession,
                    'skill' => $skillMap[$skillSlug],
                ]);
                if ($existingLink) {
                    continue;
                }

                $link = new ProfessionSkill();
                $link->setProfession($profession);
                $link->setSkill($skillMap[$skillSlug]);

                $this->entityManager->persist($link);
                $count++;
            }
        }

        if ($count > 0) {
            $io->text(sprintf('  Created %d profession-skill links', $count));
        }

        return $count;
    }

    /**
     * Add stat bonuses to a skill based on a stat-value map.
     *
     * @param array<string, int> $bonuses Map of stat abbreviation to bonus points
     */
    private function addStatBonuses(Skill $skill, array $bonuses): void
    {
        $statMap = [
            'str' => StatType::Strength,
            'dex' => StatType::Dexterity,
            'con' => StatType::Constitution,
        ];

        foreach ($bonuses as $stat => $points) {
            if ($points <= 0 || !isset($statMap[$stat])) {
                continue;
            }

            $bonus = new SkillStatBonus();
            $bonus->setStatType($statMap[$stat]);
            $bonus->setPoints($points);
            $skill->addStatBonus($bonus);
        }
    }

    /**
     * Return all 32 profession skill definitions from the design document.
     *
     * Tier 1: 3 passives + 6 actives = 9
     * Tier 2: 4 passives + 8 actives = 12
     * Tier 3: 4 passives + 7 actives = 11
     *
     * @return array<int, array{slug: string, name: string, type: string, tier: int, bonuses: array<string, int>, description: string, duration?: int, cooldown?: int}>
     */
    private function getProfessionSkillDefinitions(): array
    {
        return [
            // Tier 1 Passives
            ['slug' => 'iron-skin', 'name' => 'Iron Skin', 'type' => 'passive', 'tier' => 1, 'bonuses' => ['con' => 2], 'description' => 'Hardened through endurance training. Permanently tougher than the average warrior.'],
            ['slug' => 'sharp-reflexes', 'name' => 'Sharp Reflexes', 'type' => 'passive', 'tier' => 1, 'bonuses' => ['dex' => 2], 'description' => 'Finely tuned reaction speed. Your body moves before your mind catches up.'],
            ['slug' => 'raw-power', 'name' => 'Raw Power', 'type' => 'passive', 'tier' => 1, 'bonuses' => ['str' => 2], 'description' => 'Baseline strength that exceeds the untrained. Every muscle fiber is primed for force.'],
            // Tier 1 Actives
            ['slug' => 'power-strike', 'name' => 'Power Strike', 'type' => 'active', 'tier' => 1, 'bonuses' => ['str' => 4], 'duration' => 45, 'cooldown' => 90, 'description' => 'Focus your strength into a concentrated surge of raw force.'],
            ['slug' => 'quick-step', 'name' => 'Quick Step', 'type' => 'active', 'tier' => 1, 'bonuses' => ['dex' => 4], 'duration' => 45, 'cooldown' => 90, 'description' => 'Accelerate your reflexes and footwork to a blur of precise movement.'],
            ['slug' => 'endurance-boost', 'name' => 'Endurance Boost', 'type' => 'active', 'tier' => 1, 'bonuses' => ['con' => 4], 'duration' => 45, 'cooldown' => 90, 'description' => 'Dig deep into your reserves for a sustained wave of stamina.'],
            ['slug' => 'focused-mind', 'name' => 'Focused Mind', 'type' => 'active', 'tier' => 1, 'bonuses' => ['str' => 2, 'dex' => 2], 'duration' => 30, 'cooldown' => 120, 'description' => 'Clear your thoughts and sharpen both power and precision in equal measure.'],
            ['slug' => 'steady-heart', 'name' => 'Steady Heart', 'type' => 'active', 'tier' => 1, 'bonuses' => ['dex' => 2, 'con' => 2], 'duration' => 30, 'cooldown' => 120, 'description' => 'Calm your breathing and synchronize your endurance with your agility.'],
            ['slug' => 'brute-force', 'name' => 'Brute Force', 'type' => 'active', 'tier' => 1, 'bonuses' => ['str' => 3, 'con' => 1], 'duration' => 45, 'cooldown' => 90, 'description' => 'Overwhelm obstacles with a raw, unrefined surge of might and grit.'],
            // Tier 2 Passives
            ['slug' => 'hardened-body', 'name' => 'Hardened Body', 'type' => 'passive', 'tier' => 2, 'bonuses' => ['str' => 1, 'con' => 3], 'description' => 'Your body has been tempered by countless trials. Endurance runs deep in your bones.'],
            ['slug' => 'lightning-nerves', 'name' => 'Lightning Nerves', 'type' => 'passive', 'tier' => 2, 'bonuses' => ['dex' => 3, 'con' => 1], 'description' => 'Your nervous system fires faster than most can comprehend. Speed is second nature.'],
            ['slug' => 'titans-grip', 'name' => "Titan's Grip", 'type' => 'passive', 'tier' => 2, 'bonuses' => ['str' => 3, 'con' => 1], 'description' => 'Your grip strength and raw force have grown beyond ordinary limits.'],
            ['slug' => 'predators-instinct', 'name' => "Predator's Instinct", 'type' => 'passive', 'tier' => 2, 'bonuses' => ['str' => 1, 'dex' => 2, 'con' => 1], 'description' => 'A balanced sharpening of combat instincts — stronger, faster, tougher.'],
            // Tier 2 Actives
            ['slug' => 'berserker-rage', 'name' => 'Berserker Rage', 'type' => 'active', 'tier' => 2, 'bonuses' => ['str' => 7], 'duration' => 30, 'cooldown' => 180, 'description' => 'Unleash a primal fury that turns your muscles into instruments of destruction.'],
            ['slug' => 'shadow-step', 'name' => 'Shadow Step', 'type' => 'active', 'tier' => 2, 'bonuses' => ['dex' => 7], 'duration' => 30, 'cooldown' => 180, 'description' => 'Move with supernatural quickness, leaving only an afterimage in your wake.'],
            ['slug' => 'iron-will', 'name' => 'Iron Will', 'type' => 'active', 'tier' => 2, 'bonuses' => ['con' => 7], 'duration' => 30, 'cooldown' => 180, 'description' => 'Steel your body against all fatigue. Endurance beyond mortal limits.'],
            ['slug' => 'war-cry', 'name' => 'War Cry', 'type' => 'active', 'tier' => 2, 'bonuses' => ['str' => 4, 'con' => 3], 'duration' => 45, 'cooldown' => 120, 'description' => 'A thunderous shout that surges power and resilience through your body.'],
            ['slug' => 'wind-walk', 'name' => 'Wind Walk', 'type' => 'active', 'tier' => 2, 'bonuses' => ['dex' => 4, 'con' => 3], 'duration' => 45, 'cooldown' => 120, 'description' => 'Move with the lightness of air and the stamina of the eternal wind.'],
            ['slug' => 'stone-shield', 'name' => 'Stone Shield', 'type' => 'active', 'tier' => 2, 'bonuses' => ['str' => 3, 'con' => 4], 'duration' => 45, 'cooldown' => 120, 'description' => 'Harden your body like living rock — unyielding force meets unbreakable defense.'],
            ['slug' => 'precision-strike', 'name' => 'Precision Strike', 'type' => 'active', 'tier' => 2, 'bonuses' => ['str' => 2, 'dex' => 5], 'duration' => 30, 'cooldown' => 150, 'description' => 'Channel pinpoint accuracy and explosive speed into every movement.'],
            ['slug' => 'adrenaline-rush', 'name' => 'Adrenaline Rush', 'type' => 'active', 'tier' => 2, 'bonuses' => ['str' => 3, 'dex' => 3, 'con' => 2], 'duration' => 20, 'cooldown' => 240, 'description' => 'A chemical surge that temporarily elevates every physical attribute at once.'],
            // Tier 3 Passives
            ['slug' => 'unbreakable-spirit', 'name' => 'Unbreakable Spirit', 'type' => 'passive', 'tier' => 3, 'bonuses' => ['dex' => 2, 'con' => 5], 'description' => 'Your willpower and body have merged into an engine of limitless endurance.'],
            ['slug' => 'phantom-grace', 'name' => 'Phantom Grace', 'type' => 'passive', 'tier' => 3, 'bonuses' => ['dex' => 5, 'con' => 2], 'description' => 'You move with the fluidity of a specter — untouchable, inexorable, perfect.'],
            ['slug' => 'titans-legacy', 'name' => "Titan's Legacy", 'type' => 'passive', 'tier' => 3, 'bonuses' => ['str' => 5, 'con' => 2], 'description' => 'The strength of ancient titans flows through your bloodline. Raw power incarnate.'],
            ['slug' => 'perfect-balance', 'name' => 'Perfect Balance', 'type' => 'passive', 'tier' => 3, 'bonuses' => ['str' => 3, 'dex' => 3, 'con' => 2], 'description' => 'Absolute equilibrium across all attributes — a rare state achieved only by true masters.'],
            // Tier 3 Actives (Ultimates)
            ['slug' => 'wrath-of-the-titans', 'name' => 'Wrath of the Titans', 'type' => 'active', 'tier' => 3, 'bonuses' => ['str' => 10, 'con' => 5], 'duration' => 30, 'cooldown' => 360, 'description' => 'Invoke the devastating power of the ancient titans. Your strength becomes the stuff of legend.'],
            ['slug' => 'wind-gods-blessing', 'name' => "Wind God's Blessing", 'type' => 'active', 'tier' => 3, 'bonuses' => ['dex' => 10, 'con' => 5], 'duration' => 30, 'cooldown' => 360, 'description' => 'The wind god grants you supernatural speed and the endurance to sustain it.'],
            ['slug' => 'eternal-fortitude', 'name' => 'Eternal Fortitude', 'type' => 'active', 'tier' => 3, 'bonuses' => ['str' => 5, 'con' => 10], 'duration' => 30, 'cooldown' => 360, 'description' => 'Your body becomes an immovable fortress, shrugging off exhaustion as if it were nothing.'],
            ['slug' => 'avatar-of-war', 'name' => 'Avatar of War', 'type' => 'active', 'tier' => 3, 'bonuses' => ['str' => 7, 'dex' => 7], 'duration' => 20, 'cooldown' => 480, 'description' => 'Become a living weapon — an avatar of pure martial devastation.'],
            ['slug' => 'avatar-of-the-storm', 'name' => 'Avatar of the Storm', 'type' => 'active', 'tier' => 3, 'bonuses' => ['dex' => 7, 'con' => 7], 'duration' => 20, 'cooldown' => 480, 'description' => 'Channel the storm\'s fury — lightning speed and the endurance of a hurricane.'],
            ['slug' => 'avatar-of-the-mountain', 'name' => 'Avatar of the Mountain', 'type' => 'active', 'tier' => 3, 'bonuses' => ['str' => 7, 'con' => 7], 'duration' => 20, 'cooldown' => 480, 'description' => 'Become an immovable titan — the raw power and resilience of the mountain itself.'],
            ['slug' => 'transcendence', 'name' => 'Transcendence', 'type' => 'active', 'tier' => 3, 'bonuses' => ['str' => 5, 'dex' => 5, 'con' => 5], 'duration' => 15, 'cooldown' => 480, 'description' => 'Briefly transcend mortal limits. Every attribute surges to superhuman levels.'],
        ];
    }

    /**
     * Return the complete profession-to-skill mapping from the design document.
     *
     * Each entry maps a profession slug to the skill slugs it should be linked to.
     * Covers all 48 professions across 16 categories and 3 tiers.
     *
     * @return array<int, array{profession_slug: string, skill_slugs: array<string>}>
     */
    private function getProfessionSkillMappings(): array
    {
        return [
            // 1. Combat (STR / CON)
            ['profession_slug' => 'fighter', 'skill_slugs' => ['raw-power', 'power-strike', 'brute-force']],
            ['profession_slug' => 'gladiator', 'skill_slugs' => ['titans-grip', 'berserker-rage', 'war-cry', 'adrenaline-rush']],
            ['profession_slug' => 'titan-breaker', 'skill_slugs' => ['titans-legacy', 'wrath-of-the-titans', 'avatar-of-war']],

            // 2. Running (DEX / CON)
            ['profession_slug' => 'rogue', 'skill_slugs' => ['sharp-reflexes', 'quick-step', 'steady-heart']],
            ['profession_slug' => 'pathfinder', 'skill_slugs' => ['lightning-nerves', 'shadow-step', 'wind-walk', 'adrenaline-rush']],
            ['profession_slug' => 'wind-rider', 'skill_slugs' => ['phantom-grace', 'wind-gods-blessing', 'avatar-of-the-storm']],

            // 3. Walking (CON / DEX)
            ['profession_slug' => 'wanderer', 'skill_slugs' => ['iron-skin', 'endurance-boost', 'steady-heart']],
            ['profession_slug' => 'pilgrim', 'skill_slugs' => ['hardened-body', 'iron-will', 'wind-walk', 'stone-shield']],
            ['profession_slug' => 'eternal-strider', 'skill_slugs' => ['unbreakable-spirit', 'eternal-fortitude', 'avatar-of-the-storm']],

            // 4. Cycling (CON / STR)
            ['profession_slug' => 'rider', 'skill_slugs' => ['iron-skin', 'endurance-boost', 'brute-force']],
            ['profession_slug' => 'dark-rider', 'skill_slugs' => ['hardened-body', 'iron-will', 'war-cry', 'stone-shield']],
            ['profession_slug' => 'iron-cavalier', 'skill_slugs' => ['titans-legacy', 'wrath-of-the-titans', 'avatar-of-the-mountain']],

            // 5. Swimming (CON / STR)
            ['profession_slug' => 'tide-warden', 'skill_slugs' => ['iron-skin', 'endurance-boost', 'brute-force']],
            ['profession_slug' => 'depth-walker', 'skill_slugs' => ['hardened-body', 'iron-will', 'stone-shield', 'adrenaline-rush']],
            ['profession_slug' => 'abyssal-lord', 'skill_slugs' => ['unbreakable-spirit', 'eternal-fortitude', 'avatar-of-the-mountain']],

            // 6. Strength (STR / CON)
            ['profession_slug' => 'brawler', 'skill_slugs' => ['raw-power', 'power-strike', 'brute-force']],
            ['profession_slug' => 'destroyer', 'skill_slugs' => ['titans-grip', 'berserker-rage', 'war-cry', 'stone-shield']],
            ['profession_slug' => 'tyrant', 'skill_slugs' => ['titans-legacy', 'wrath-of-the-titans', 'avatar-of-the-mountain']],

            // 7. Flexibility (DEX / CON)
            ['profession_slug' => 'monk', 'skill_slugs' => ['sharp-reflexes', 'quick-step', 'focused-mind']],
            ['profession_slug' => 'bladedancer', 'skill_slugs' => ['lightning-nerves', 'shadow-step', 'wind-walk', 'precision-strike']],
            ['profession_slug' => 'phantom-dancer', 'skill_slugs' => ['phantom-grace', 'wind-gods-blessing', 'transcendence']],

            // 8. Cardio (CON / DEX)
            ['profession_slug' => 'scout', 'skill_slugs' => ['iron-skin', 'endurance-boost', 'steady-heart']],
            ['profession_slug' => 'storm-chaser', 'skill_slugs' => ['hardened-body', 'iron-will', 'wind-walk', 'adrenaline-rush']],
            ['profession_slug' => 'tempest-warden', 'skill_slugs' => ['unbreakable-spirit', 'eternal-fortitude', 'avatar-of-the-storm']],

            // 9. Dance (DEX / CON)
            ['profession_slug' => 'minstrel', 'skill_slugs' => ['sharp-reflexes', 'quick-step', 'steady-heart']],
            ['profession_slug' => 'swordsinger', 'skill_slugs' => ['lightning-nerves', 'shadow-step', 'wind-walk', 'precision-strike']],
            ['profession_slug' => 'celestial-bard', 'skill_slugs' => ['phantom-grace', 'wind-gods-blessing', 'transcendence']],

            // 10. Winter Sports (DEX / STR)
            ['profession_slug' => 'frost-scout', 'skill_slugs' => ['sharp-reflexes', 'quick-step', 'focused-mind']],
            ['profession_slug' => 'ice-warden', 'skill_slugs' => ['lightning-nerves', 'shadow-step', 'precision-strike', 'adrenaline-rush']],
            ['profession_slug' => 'boreal-sovereign', 'skill_slugs' => ['phantom-grace', 'wind-gods-blessing', 'avatar-of-war']],

            // 11. Racquet Sports (DEX / STR)
            ['profession_slug' => 'duelist', 'skill_slugs' => ['sharp-reflexes', 'quick-step', 'focused-mind']],
            ['profession_slug' => 'treasure-hunter', 'skill_slugs' => ['lightning-nerves', 'shadow-step', 'precision-strike', 'adrenaline-rush']],
            ['profession_slug' => 'phantom-striker', 'skill_slugs' => ['phantom-grace', 'wind-gods-blessing', 'avatar-of-war']],

            // 12. Team Sports (STR / DEX)
            ['profession_slug' => 'squire', 'skill_slugs' => ['raw-power', 'power-strike', 'focused-mind']],
            ['profession_slug' => 'warlord', 'skill_slugs' => ['predators-instinct', 'berserker-rage', 'war-cry', 'adrenaline-rush']],
            ['profession_slug' => 'grand-marshal', 'skill_slugs' => ['perfect-balance', 'wrath-of-the-titans', 'avatar-of-war']],

            // 13. Water Sports (STR / CON)
            ['profession_slug' => 'deckhand', 'skill_slugs' => ['raw-power', 'power-strike', 'brute-force']],
            ['profession_slug' => 'sea-raider', 'skill_slugs' => ['titans-grip', 'berserker-rage', 'stone-shield', 'adrenaline-rush']],
            ['profession_slug' => 'storm-sovereign', 'skill_slugs' => ['titans-legacy', 'wrath-of-the-titans', 'avatar-of-the-mountain']],

            // 14. Outdoor (DEX / STR)
            ['profession_slug' => 'ranger', 'skill_slugs' => ['sharp-reflexes', 'quick-step', 'focused-mind']],
            ['profession_slug' => 'hawkeye', 'skill_slugs' => ['lightning-nerves', 'shadow-step', 'precision-strike', 'war-cry']],
            ['profession_slug' => 'silver-ranger', 'skill_slugs' => ['phantom-grace', 'wind-gods-blessing', 'avatar-of-war']],

            // 15. Mind & Body (CON / DEX)
            ['profession_slug' => 'mystic', 'skill_slugs' => ['iron-skin', 'endurance-boost', 'steady-heart']],
            ['profession_slug' => 'prophet', 'skill_slugs' => ['hardened-body', 'iron-will', 'wind-walk', 'stone-shield']],
            ['profession_slug' => 'archmage', 'skill_slugs' => ['unbreakable-spirit', 'eternal-fortitude', 'transcendence']],

            // 16. Other (CON / STR)
            ['profession_slug' => 'adventurer', 'skill_slugs' => ['iron-skin', 'endurance-boost', 'brute-force']],
            ['profession_slug' => 'warsmith', 'skill_slugs' => ['predators-instinct', 'iron-will', 'war-cry', 'adrenaline-rush']],
            ['profession_slug' => 'chaos-vanguard', 'skill_slugs' => ['perfect-balance', 'eternal-fortitude', 'transcendence']],
        ];
    }
}
