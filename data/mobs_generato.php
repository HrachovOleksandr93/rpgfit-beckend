<?php

// Prevent infinity loop by generating all valid combinations first, then picking from them.
$targetMobsPerLevel = 20;
$maxLevel = 100;
$mobsData = [];

// 100 Base Mobs distributed logically across levels 1 to 100
$baseMobNames = [
    'Rat', 'Bat', 'Slime', 'Spider', 'Beetle', 'Crab', 'Snake', 'Wolf', 'Goblin', 'Kobold',
    'Boar', 'Skeleton', 'Zombie', 'Hound', 'Bandit', 'Thief', 'Mercenary', 'Orc', 'Lizardman', 'Harpy',
    'Gnoll', 'Troll', 'Ogre', 'Golem', 'Gargoyle', 'Ghost', 'Wraith', 'Vampire', 'Minotaur', 'Centaur',
    'Cyclops', 'Giant', 'Treant', 'Dryad', 'Nymph', 'Sprite', 'Fairy', 'Wisp', 'Banshee', 'Lich',
    'Mummy', 'Sphinx', 'Djinn', 'Efreet', 'Rakshasa', 'Naga', 'Siren', 'Merfolk', 'Sahagin', 'Kuo-toa',
    'Illithid', 'Beholder', 'Mimic', 'Cube', 'Rust Monster', 'Owlbear', 'Displacer Beast', 'Bulette', 'Ankheg', 'Worm',
    'Roper', 'Piercer', 'Shrieker', 'Myconid', 'Vegepygmy', 'Thri-kreen', 'Yuan-ti', 'Githyanki', 'Githzerai', 'Slaad',
    'Modron', 'Inevitable', 'Deva', 'Planetar', 'Solar', 'Pit Fiend', 'Balor', 'Marilith', 'Glabrezu', 'Vrock',
    'Hezrou', 'Quasit', 'Imp', 'Lemure', 'Dretch', 'Manes', 'Elemental', 'Succubus', 'Drake', 'Demon',
    'Behemoth', 'Wyrm', 'Dragon', 'Basilisk', 'Manticore', 'Chimera', 'Griffon', 'Hydra', 'Kraken', 'Leviathan'
];

$baseMobs = [];
foreach ($baseMobNames as $index => $name) {
    // Distribute base levels from 1 to 100
    $baseMobs[] = ['name' => $name, 'base_level' => $index + 1];
}

// 104 Prefixes categorized by their level modifier and rarity
$prefixes = [
    // -2 (Common, Weakened)
    ['word' => 'Sick', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A sickly '],
    ['word' => 'Weak', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A feeble '],
    ['word' => 'Old', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'An aged '],
    ['word' => 'Starving', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A hungry '],
    ['word' => 'Tiny', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'An unusually small '],
    ['word' => 'Pathetic', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A sad-looking '],
    ['word' => 'Feeble', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A severely weakened '],
    ['word' => 'Frail', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A fragile '],
    ['word' => 'Broken', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A battered '],
    ['word' => 'Blind', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A sightless '],
    ['word' => 'Deaf', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A deafened '],
    ['word' => 'Clumsy', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'An uncoordinated '],
    ['word' => 'Slow', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A sluggish '],
    ['word' => 'Lethargic', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'An inactive '],
    ['word' => 'Dying', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A barely breathing '],
    ['word' => 'Emaciated', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A starving '],
    ['word' => 'Wounded', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'An injured '],
    ['word' => 'Crippled', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A limping '],
    ['word' => 'Decrepit', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A ruined '],
    ['word' => 'Shabby', 'modifier' => -2, 'rarity' => 'Common', 'desc_prefix' => 'A poor quality '],

    // -1 (Common, Young/Lesser)
    ['word' => 'Young', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A young '],
    ['word' => 'Small', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A smaller than average '],
    ['word' => 'Lesser', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'An inferior '],
    ['word' => 'Scrawny', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A thin '],
    ['word' => 'Hesitant', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A cautious '],
    ['word' => 'Confused', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A disoriented '],
    ['word' => 'Lost', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A wandering '],
    ['word' => 'Timid', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A fearful '],
    ['word' => 'Runty', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'The runt of the litter '],
    ['word' => 'Underweight', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A light '],
    ['word' => 'Dull', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A slow-witted '],
    ['word' => 'Rusty', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'An out-of-practice '],
    ['word' => 'Tarnished', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A faded '],
    ['word' => 'Chipped', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A damaged '],
    ['word' => 'Juvenile', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'An immature '],
    ['word' => 'Skittish', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A jumpy '],
    ['word' => 'Cowardly', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A fleeing '],
    ['word' => 'Frightened', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A scared '],
    ['word' => 'Nervous', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'An anxious '],
    ['word' => 'Meek', 'modifier' => -1, 'rarity' => 'Common', 'desc_prefix' => 'A submissive '],

    // 0 (Normal, Biomes/Colors)
    ['word' => '', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A common '],
    ['word' => 'Black', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A dark-colored '],
    ['word' => 'White', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A pale '],
    ['word' => 'Brown', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A brown-toned '],
    ['word' => 'Grey', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'An ashen '],
    ['word' => 'Green', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A green-hued '],
    ['word' => 'Blue', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A blue-tinted '],
    ['word' => 'Red', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A crimson '],
    ['word' => 'Yellow', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A golden '],
    ['word' => 'Spotted', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A speckled '],
    ['word' => 'Striped', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A patterned '],
    ['word' => 'Forest', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A woodland '],
    ['word' => 'Cave', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A subterranean '],
    ['word' => 'Mountain', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A highland '],
    ['word' => 'Swamp', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A marsh-dwelling '],
    ['word' => 'Desert', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A sand-dwelling '],
    ['word' => 'Plains', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A flatland '],
    ['word' => 'River', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A freshwater '],
    ['word' => 'Tundra', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A cold-climate '],
    ['word' => 'Jungle', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A tropical '],
    ['word' => 'Island', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'An isolated '],
    ['word' => 'Coastal', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A shoreline '],
    ['word' => 'Valley', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'A lowland '],
    ['word' => 'Wild', 'modifier' => 0, 'rarity' => 'Normal', 'desc_prefix' => 'An untamed '],

    // +1 (Rare, Fierce/Agile)
    ['word' => 'Fierce', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'An aggressive '],
    ['word' => 'Angry', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'An enraged '],
    ['word' => 'Rabid', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A diseased, violent '],
    ['word' => 'Savage', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A feral '],
    ['word' => 'Brutal', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A merciless '],
    ['word' => 'Vicious', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A spiteful '],
    ['word' => 'Bloodthirsty', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A murderous '],
    ['word' => 'Cruel', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A sadistic '],
    ['word' => 'Ruthless', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'An unforgiving '],
    ['word' => 'Relentless', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A persistent '],
    ['word' => 'Cunning', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A clever '],
    ['word' => 'Sly', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A deceptive '],
    ['word' => 'Quick', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A fast-moving '],
    ['word' => 'Agile', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A highly mobile '],
    ['word' => 'Nimble', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A graceful '],
    ['word' => 'Swift', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A rapid '],
    ['word' => 'Strong', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A muscular '],
    ['word' => 'Tough', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A resilient '],
    ['word' => 'Hardened', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A battle-tested '],
    ['word' => 'Sturdy', 'modifier' => 1, 'rarity' => 'Rare', 'desc_prefix' => 'A solid '],

    // +2 (Epic, Boss-like)
    ['word' => 'Dire', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A massive, ancient '],
    ['word' => 'Elder', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A primordial '],
    ['word' => 'Greater', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'An evolved, superior '],
    ['word' => 'Giant', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'An oversized '],
    ['word' => 'Huge', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A colossal '],
    ['word' => 'Massive', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'An incredibly heavy '],
    ['word' => 'Hulking', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A looming, dangerous '],
    ['word' => 'Towering', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'An impossibly tall '],
    ['word' => 'Monstrous', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A horrifyingly mutated '],
    ['word' => 'Fearsome', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A terrifying '],
    ['word' => 'Terrifying', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A panic-inducing '],
    ['word' => 'Horrifying', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A mind-bending '],
    ['word' => 'Nightmare', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A demonic, shadow-infused '],
    ['word' => 'Dread', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'An aura-projecting '],
    ['word' => 'Doom', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'An apocalyptic '],
    ['word' => 'Elite', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A highly trained '],
    ['word' => 'Champion', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'The peak specimen of a '],
    ['word' => 'Warlord', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A commanding '],
    ['word' => 'Corrupted', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'A dark magic infused '],
    ['word' => 'Cursed', 'modifier' => 2, 'rarity' => 'Epic', 'desc_prefix' => 'An undead or hexed ']
];

function getRequiredXp($level) {
    return floor(4.2 * pow($level, 2) + 28 * $level);
}

function generateSlug($string) {
    $string = strtolower(trim($string));
    return preg_replace('/[^a-z0-9-]+/', '-', $string);
}

for ($level = 1; $level <= $maxLevel; $level++) {
    $validCombinations = [];

    // Find all combinations that match the exact target level
    foreach ($baseMobs as $base) {
        foreach ($prefixes as $prefix) {
            if ($base['base_level'] + $prefix['modifier'] === $level) {
                $validCombinations[] = [
                    'base' => $base,
                    'prefix' => $prefix
                ];
            }
        }
    }

    // Shuffle combinations to ensure variety across generations
    shuffle($validCombinations);

    $selectedCount = 0;
    foreach ($validCombinations as $combo) {
        if ($selectedCount >= $targetMobsPerLevel) {
            break; 
        }

        $baseName = $combo['base']['name'];
        $prefixData = $combo['prefix'];

        $fullName = trim($prefixData['word'] . ' ' . $baseName);
        $slug = generateSlug($fullName);

        // Stats calculation based on provided formulas
        $baseHp = $level * 100;
        $hpVariance = rand(-20, 20) / 100;
        $finalHp = (int)round($baseHp * (1 + $hpVariance));

        $levelXp = getRequiredXp($level);
        $baseXpReward = max(1, $levelXp / 15);
        $xpVariance = rand(-10, 10) / 100;
        $finalXpReward = max(1, (int)round($baseXpReward * (1 + $xpVariance)));

        $description = trim($prefixData['desc_prefix'] . strtolower($baseName) . '.');

        $mobsData[] = [
            'name' => $fullName,
            'slug' => $slug,
            'level' => $level,
            'hp' => $finalHp,
            'xpReward' => $finalXpReward,
            'description' => ucfirst($description),
            'image' => "mob_" . $slug . ".png",
            'rarity' => $prefixData['rarity']
        ];

        $selectedCount++;
    }
}

// Write generated data to CSV file
$fp = fopen('mobs_massive_import.csv', 'w');
fputcsv($fp, ['name', 'slug', 'level', 'hp', 'xpReward', 'description', 'image', 'rarity']);

foreach ($mobsData as $mob) {
    fputcsv($fp, $mob);
}

fclose($fp);
echo "Generated " . count($mobsData) . " unique mobs perfectly balanced and saved to CSV.\n";

?>