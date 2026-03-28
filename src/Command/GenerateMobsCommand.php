<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Mob\Entity\Mob;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generates mob definitions procedurally and inserts them directly into the database.
 *
 * Uses a base mob list (100 creatures) combined with prefix modifiers (104 prefixes)
 * to create unique mob variants for each level 1-100. Prefixes adjust the mob's
 * effective level via a modifier (-2 to +2) and assign rarity tiers.
 *
 * HP formula: 20 * level^1.5 + 40 (±20% random variance)
 * XP formula: xp_for_level / 15 * rarity_multiplier (±10% variance)
 *   where xp_for_level = 4.2 * level^2 + 28 * level (matches our leveling curve)
 *   This means ~15 common mob kills = 1 level worth of XP.
 *
 * Rarity multipliers for XP reward:
 *   common = 1.0x, uncommon = 1.3x, rare = 1.6x, epic = 2.0x, legendary = 3.0x
 *
 * Usage: php bin/console app:generate-mobs [--mobs-per-level=20] [--dry-run]
 */
#[AsCommand(
    name: 'app:generate-mobs',
    description: 'Procedurally generate mob definitions and insert into database',
)]
class GenerateMobsCommand extends Command
{
    private const BATCH_SIZE = 50;

    // Rarity multipliers for XP reward — rarer mobs give more XP
    private const RARITY_XP_MULTIPLIER = [
        'common' => 1.0,
        'uncommon' => 1.3,
        'rare' => 1.6,
        'epic' => 2.0,
        'legendary' => 3.0,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('mobs-per-level', null, InputOption::VALUE_REQUIRED, 'Target mobs per level', '20')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be generated without writing to DB')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Delete all existing mobs before generating');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $targetPerLevel = (int) $input->getOption('mobs-per-level');
        $dryRun = $input->getOption('dry-run');
        $clear = $input->getOption('clear');

        if ($dryRun) {
            $io->note('Dry-run mode: no changes will be persisted.');
        }

        // Optionally clear existing mobs
        if ($clear && !$dryRun) {
            $deleted = $this->entityManager->createQuery('DELETE FROM App\Domain\Mob\Entity\Mob')->execute();
            $io->warning(sprintf('Deleted %d existing mobs.', $deleted));
        }

        $baseMobs = $this->getBaseMobs();
        $prefixes = $this->getPrefixes();

        $totalCreated = 0;
        $usedSlugs = [];

        for ($level = 1; $level <= 100; $level++) {
            // Find all base+prefix combinations that produce this exact level
            $validCombos = [];
            foreach ($baseMobs as $base) {
                foreach ($prefixes as $prefix) {
                    $effectiveLevel = $base['baseLevel'] + $prefix['modifier'];
                    if ($effectiveLevel === $level) {
                        $validCombos[] = ['base' => $base, 'prefix' => $prefix];
                    }
                }
            }

            // Shuffle for variety, then pick up to target count
            shuffle($validCombos);
            $count = 0;

            foreach ($validCombos as $combo) {
                if ($count >= $targetPerLevel) {
                    break;
                }

                $baseName = $combo['base']['name'];
                $prefixData = $combo['prefix'];

                $fullName = trim(($prefixData['word'] !== '' ? $prefixData['word'] . ' ' : '') . $baseName);
                $slug = $this->generateSlug($fullName) . '-' . $level;

                // Ensure slug uniqueness
                if (isset($usedSlugs[$slug])) {
                    $slug .= '-' . mt_rand(1, 999);
                }
                if (isset($usedSlugs[$slug])) {
                    continue; // Skip if still duplicate
                }
                $usedSlugs[$slug] = true;

                // Calculate HP: 20 * level^1.5 + 40, with ±20% variance
                $baseHp = 20.0 * pow($level, 1.5) + 40.0;
                $hpVariance = mt_rand(-20, 20) / 100.0;
                $finalHp = max(1, (int) round($baseHp * (1.0 + $hpVariance)));

                // Calculate XP reward: (xp_for_level / 15) * rarity_multiplier, with ±10% variance
                // xp_for_level = 4.2 * level^2 + 28 * level (our leveling formula)
                $xpForLevel = 4.2 * $level * $level + 28.0 * $level;
                $baseXpReward = $xpForLevel / 15.0;
                $rarityKey = strtolower($prefixData['rarity']);
                if ($rarityKey === 'normal') {
                    $rarityKey = 'common';
                }
                $rarityMultiplier = self::RARITY_XP_MULTIPLIER[$rarityKey] ?? 1.0;
                $xpVariance = mt_rand(-10, 10) / 100.0;
                $finalXpReward = max(1, (int) round($baseXpReward * $rarityMultiplier * (1.0 + $xpVariance)));

                // Map rarity string to ItemRarity enum
                $rarity = ItemRarity::tryFrom($rarityKey) ?? ItemRarity::Common;

                // Build description
                $description = ucfirst(trim($prefixData['descPrefix'] . strtolower($baseName) . '.'));

                if (!$dryRun) {
                    $mob = new Mob();
                    $mob->setName($fullName)
                        ->setSlug($slug)
                        ->setLevel($level)
                        ->setHp($finalHp)
                        ->setXpReward($finalXpReward)
                        ->setRarity($rarity)
                        ->setDescription($description);

                    $this->entityManager->persist($mob);
                }

                $totalCreated++;
                $count++;

                // Batch flush for memory efficiency
                if (!$dryRun && $totalCreated % self::BATCH_SIZE === 0) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                }
            }

            // Progress indicator every 10 levels
            if ($level % 10 === 0) {
                $io->text(sprintf('Level %d: %d mobs generated (total: %d)', $level, $count, $totalCreated));
            }
        }

        // Final flush
        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->success(sprintf('Generated %d mobs across 100 levels.', $totalCreated));

        // Show stats summary
        $this->showStats($io, $totalCreated);

        return Command::SUCCESS;
    }

    /**
     * Display generation statistics with HP/XP samples for milestone levels.
     */
    private function showStats(SymfonyStyle $io, int $total): void
    {
        $io->section('Milestone Level Stats (base values, no variance)');

        $rows = [];
        foreach ([1, 5, 10, 20, 30, 50, 70, 90, 100] as $level) {
            $hp = (int) round(20.0 * pow($level, 1.5) + 40.0);
            $xpForLevel = 4.2 * $level * $level + 28.0 * $level;
            $xpReward = (int) round($xpForLevel / 15.0);
            $rows[] = [$level, $hp, $xpReward, (int) round($hp * 0.8), (int) round($hp * 1.2)];
        }

        $io->table(
            ['Level', 'Base HP', 'Base XP Reward', 'Min HP (-20%)', 'Max HP (+20%)'],
            $rows,
        );
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        return trim($slug, '-');
    }

    /**
     * 100 base mob types, each assigned a base level 1-100.
     * Prefix modifiers shift them ±2 levels to create variety at each level.
     *
     * @return array<array{name: string, baseLevel: int}>
     */
    private function getBaseMobs(): array
    {
        $names = [
            'Rat', 'Bat', 'Slime', 'Spider', 'Beetle', 'Crab', 'Snake', 'Wolf', 'Goblin', 'Kobold',
            'Boar', 'Skeleton', 'Zombie', 'Hound', 'Bandit', 'Thief', 'Mercenary', 'Orc', 'Lizardman', 'Harpy',
            'Gnoll', 'Troll', 'Ogre', 'Golem', 'Gargoyle', 'Ghost', 'Wraith', 'Vampire', 'Minotaur', 'Centaur',
            'Cyclops', 'Giant', 'Treant', 'Dryad', 'Nymph', 'Sprite', 'Fairy', 'Wisp', 'Banshee', 'Lich',
            'Mummy', 'Sphinx', 'Djinn', 'Efreet', 'Rakshasa', 'Naga', 'Siren', 'Merfolk', 'Sahagin', 'Kuo-toa',
            'Illithid', 'Beholder', 'Mimic', 'Cube', 'Rust Monster', 'Owlbear', 'Displacer Beast', 'Bulette', 'Ankheg', 'Worm',
            'Roper', 'Piercer', 'Shrieker', 'Myconid', 'Vegepygmy', 'Thri-kreen', 'Yuan-ti', 'Githyanki', 'Githzerai', 'Slaad',
            'Modron', 'Inevitable', 'Deva', 'Planetar', 'Solar', 'Pit Fiend', 'Balor', 'Marilith', 'Glabrezu', 'Vrock',
            'Hezrou', 'Quasit', 'Imp', 'Lemure', 'Dretch', 'Manes', 'Elemental', 'Succubus', 'Drake', 'Demon',
            'Behemoth', 'Wyrm', 'Dragon', 'Basilisk', 'Manticore', 'Chimera', 'Griffon', 'Hydra', 'Kraken', 'Leviathan',
        ];

        $mobs = [];
        foreach ($names as $i => $name) {
            $mobs[] = ['name' => $name, 'baseLevel' => $i + 1];
        }

        return $mobs;
    }

    /**
     * 104 prefixes grouped by level modifier and rarity tier.
     *
     * Modifier -2: Weakened variants (common) — e.g., "Sick Wolf", "Blind Spider"
     * Modifier -1: Young/lesser variants (common) — e.g., "Young Dragon", "Small Ogre"
     * Modifier  0: Normal/biome variants (common) — e.g., "Forest Wolf", "Cave Spider"
     * Modifier +1: Fierce variants (rare) — e.g., "Savage Wolf", "Cunning Goblin"
     * Modifier +2: Boss-like variants (epic) — e.g., "Dire Wolf", "Elder Dragon"
     *
     * @return array<array{word: string, modifier: int, rarity: string, descPrefix: string}>
     */
    private function getPrefixes(): array
    {
        return [
            // Modifier -2: Weakened (common rarity)
            ['word' => 'Sick', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A sickly '],
            ['word' => 'Weak', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A feeble '],
            ['word' => 'Old', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'An aged '],
            ['word' => 'Starving', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A hungry '],
            ['word' => 'Tiny', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'An unusually small '],
            ['word' => 'Pathetic', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A sad-looking '],
            ['word' => 'Feeble', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A severely weakened '],
            ['word' => 'Frail', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A fragile '],
            ['word' => 'Broken', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A battered '],
            ['word' => 'Blind', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A sightless '],
            ['word' => 'Deaf', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A deafened '],
            ['word' => 'Clumsy', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'An uncoordinated '],
            ['word' => 'Slow', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A sluggish '],
            ['word' => 'Lethargic', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'An inactive '],
            ['word' => 'Dying', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A barely breathing '],
            ['word' => 'Emaciated', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A starving '],
            ['word' => 'Wounded', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'An injured '],
            ['word' => 'Crippled', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A limping '],
            ['word' => 'Decrepit', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A ruined '],
            ['word' => 'Shabby', 'modifier' => -2, 'rarity' => 'common', 'descPrefix' => 'A poor quality '],

            // Modifier -1: Young/lesser (common rarity)
            ['word' => 'Young', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A young '],
            ['word' => 'Small', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A smaller than average '],
            ['word' => 'Lesser', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'An inferior '],
            ['word' => 'Scrawny', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A thin '],
            ['word' => 'Hesitant', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A cautious '],
            ['word' => 'Confused', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A disoriented '],
            ['word' => 'Lost', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A wandering '],
            ['word' => 'Timid', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A fearful '],
            ['word' => 'Runty', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'The runt of the litter '],
            ['word' => 'Underweight', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A light '],
            ['word' => 'Dull', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A slow-witted '],
            ['word' => 'Rusty', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'An out-of-practice '],
            ['word' => 'Tarnished', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A faded '],
            ['word' => 'Chipped', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A damaged '],
            ['word' => 'Juvenile', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'An immature '],
            ['word' => 'Skittish', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A jumpy '],
            ['word' => 'Cowardly', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A fleeing '],
            ['word' => 'Frightened', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A scared '],
            ['word' => 'Nervous', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'An anxious '],
            ['word' => 'Meek', 'modifier' => -1, 'rarity' => 'common', 'descPrefix' => 'A submissive '],

            // Modifier 0: Normal/biome variants (common/uncommon rarity)
            ['word' => '', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A common '],
            ['word' => 'Black', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A dark-colored '],
            ['word' => 'White', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A pale '],
            ['word' => 'Brown', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A brown-toned '],
            ['word' => 'Grey', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'An ashen '],
            ['word' => 'Green', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A green-hued '],
            ['word' => 'Blue', 'modifier' => 0, 'rarity' => 'uncommon', 'descPrefix' => 'A blue-tinted '],
            ['word' => 'Red', 'modifier' => 0, 'rarity' => 'uncommon', 'descPrefix' => 'A crimson '],
            ['word' => 'Yellow', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A golden '],
            ['word' => 'Spotted', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A speckled '],
            ['word' => 'Striped', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A patterned '],
            ['word' => 'Forest', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A woodland '],
            ['word' => 'Cave', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A subterranean '],
            ['word' => 'Mountain', 'modifier' => 0, 'rarity' => 'uncommon', 'descPrefix' => 'A highland '],
            ['word' => 'Swamp', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A marsh-dwelling '],
            ['word' => 'Desert', 'modifier' => 0, 'rarity' => 'uncommon', 'descPrefix' => 'A sand-dwelling '],
            ['word' => 'Plains', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A flatland '],
            ['word' => 'River', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A freshwater '],
            ['word' => 'Tundra', 'modifier' => 0, 'rarity' => 'uncommon', 'descPrefix' => 'A cold-climate '],
            ['word' => 'Jungle', 'modifier' => 0, 'rarity' => 'uncommon', 'descPrefix' => 'A tropical '],
            ['word' => 'Island', 'modifier' => 0, 'rarity' => 'uncommon', 'descPrefix' => 'An isolated '],
            ['word' => 'Coastal', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A shoreline '],
            ['word' => 'Valley', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'A lowland '],
            ['word' => 'Wild', 'modifier' => 0, 'rarity' => 'common', 'descPrefix' => 'An untamed '],

            // Modifier +1: Fierce (rare rarity, stronger than base)
            ['word' => 'Fierce', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'An aggressive '],
            ['word' => 'Angry', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'An enraged '],
            ['word' => 'Rabid', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A diseased, violent '],
            ['word' => 'Savage', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A feral '],
            ['word' => 'Brutal', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A merciless '],
            ['word' => 'Vicious', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A spiteful '],
            ['word' => 'Bloodthirsty', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A murderous '],
            ['word' => 'Cruel', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A sadistic '],
            ['word' => 'Ruthless', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'An unforgiving '],
            ['word' => 'Relentless', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A persistent '],
            ['word' => 'Cunning', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A clever '],
            ['word' => 'Sly', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A deceptive '],
            ['word' => 'Quick', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A fast-moving '],
            ['word' => 'Agile', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A highly mobile '],
            ['word' => 'Nimble', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A graceful '],
            ['word' => 'Swift', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A rapid '],
            ['word' => 'Strong', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A muscular '],
            ['word' => 'Tough', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A resilient '],
            ['word' => 'Hardened', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A battle-tested '],
            ['word' => 'Sturdy', 'modifier' => 1, 'rarity' => 'rare', 'descPrefix' => 'A solid '],

            // Modifier +2: Boss-like (epic rarity, significantly stronger)
            ['word' => 'Dire', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A massive, ancient '],
            ['word' => 'Elder', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A primordial '],
            ['word' => 'Greater', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'An evolved, superior '],
            ['word' => 'Giant', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'An oversized '],
            ['word' => 'Huge', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A colossal '],
            ['word' => 'Massive', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'An incredibly heavy '],
            ['word' => 'Hulking', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A looming, dangerous '],
            ['word' => 'Towering', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'An impossibly tall '],
            ['word' => 'Monstrous', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A horrifyingly mutated '],
            ['word' => 'Fearsome', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A terrifying '],
            ['word' => 'Terrifying', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A panic-inducing '],
            ['word' => 'Horrifying', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A mind-bending '],
            ['word' => 'Nightmare', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A demonic, shadow-infused '],
            ['word' => 'Dread', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'An aura-projecting '],
            ['word' => 'Doom', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'An apocalyptic '],
            ['word' => 'Elite', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A highly trained '],
            ['word' => 'Champion', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'The peak specimen of a '],
            ['word' => 'Warlord', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A commanding '],
            ['word' => 'Corrupted', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'A dark magic infused '],
            ['word' => 'Cursed', 'modifier' => 2, 'rarity' => 'epic', 'descPrefix' => 'An undead or hexed '],
        ];
    }
}
