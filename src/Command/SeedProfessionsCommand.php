<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Activity\Entity\ActivityCategory;
use App\Domain\Activity\Entity\ActivityType;
use App\Domain\Activity\Entity\Profession;
use App\Domain\Character\Enum\StatType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to seed activity categories, professions, and activity types.
 *
 * Populates the database with the 16 RPG activity categories, 48 professions
 * (3 tiers per category), and 99 activity types from the Flutter health package.
 * Idempotent by default: skips existing slugs. Use --clear to delete and re-seed.
 *
 * Usage: php bin/console app:seed-professions [--clear]
 */
#[AsCommand(
    name: 'app:seed-professions',
    description: 'Seed activity categories, professions, and activity types',
)]
class SeedProfessionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Delete existing data before seeding');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $clear = $input->getOption('clear');

        if ($clear) {
            $io->warning('Clearing existing profession data...');
            $this->clearExistingData();
            $io->info('Existing data cleared.');
        }

        // Seed categories first (professions and activity types depend on them)
        $categoryMap = $this->seedCategories($io);

        // Seed professions (depend on categories)
        $professionCount = $this->seedProfessions($io, $categoryMap);

        // Seed activity types (depend on categories)
        $activityCount = $this->seedActivityTypes($io, $categoryMap);

        $io->success(sprintf(
            'Seeding complete! Categories: %d, Professions: %d, Activity Types: %d',
            count($categoryMap),
            $professionCount,
            $activityCount,
        ));

        return Command::SUCCESS;
    }

    /**
     * Delete all existing activity types, professions, and categories.
     * Order matters due to foreign key constraints.
     */
    private function clearExistingData(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM activity_types');
        $connection->executeStatement('DELETE FROM user_professions');
        $connection->executeStatement('DELETE FROM professions');
        $connection->executeStatement('DELETE FROM activity_categories');
    }

    /**
     * Seed the 16 activity categories. Returns a map of slug => entity.
     *
     * @return array<string, ActivityCategory>
     */
    private function seedCategories(SymfonyStyle $io): array
    {
        $categories = [
            ['slug' => 'combat', 'name' => 'Combat', 'description' => 'Those who walk the path of Combat embrace the fury of close-quarters battle.'],
            ['slug' => 'running', 'name' => 'Running', 'description' => 'Runners are the fleet-footed scouts of the realm.'],
            ['slug' => 'walking', 'name' => 'Walking', 'description' => 'Walkers are the tireless pilgrims who cross entire continents on foot.'],
            ['slug' => 'cycling', 'name' => 'Cycling', 'description' => 'Cyclists are the mounted cavalry of the modern age.'],
            ['slug' => 'swimming', 'name' => 'Swimming', 'description' => 'Swimmers descend into the realm beneath the surface.'],
            ['slug' => 'strength', 'name' => 'Strength', 'description' => 'Those who choose Strength worship iron and gravity.'],
            ['slug' => 'flexibility', 'name' => 'Flexibility', 'description' => 'The flexible warriors follow an ancient path of inner mastery.'],
            ['slug' => 'cardio', 'name' => 'Cardio', 'description' => 'Cardio warriors forge their hearts into engines of war.'],
            ['slug' => 'dance', 'name' => 'Dance', 'description' => 'Dancers channel the ancient art of rhythm into physical mastery.'],
            ['slug' => 'winter_sports', 'name' => 'Winter Sports', 'description' => 'Winter warriors are forged in the crucible of ice and cold.'],
            ['slug' => 'racquet_sports', 'name' => 'Racquet Sports', 'description' => 'Racquet warriors are the duelists of the arena.'],
            ['slug' => 'team_sports', 'name' => 'Team Sports', 'description' => 'Team sport warriors command the battlefield at the head of a squad.'],
            ['slug' => 'water_sports', 'name' => 'Water Sports', 'description' => 'Water sport warriors venture beyond the shore into open water.'],
            ['slug' => 'outdoor', 'name' => 'Outdoor', 'description' => 'Outdoor warriors are the rangers and hunters of the wild.'],
            ['slug' => 'mind_body', 'name' => 'Mind & Body', 'description' => 'Mind and Body practitioners walk the quietest and most profound path.'],
            ['slug' => 'other', 'name' => 'Other', 'description' => 'The unconventional warriors forge their own paths through fitness.'],
        ];

        $repo = $this->entityManager->getRepository(ActivityCategory::class);
        $map = [];
        $created = 0;
        $skipped = 0;

        foreach ($categories as $data) {
            $existing = $repo->findOneBy(['slug' => $data['slug']]);
            if ($existing !== null) {
                $map[$data['slug']] = $existing;
                $skipped++;
                continue;
            }

            $entity = new ActivityCategory();
            $entity->setSlug($data['slug'])
                ->setName($data['name'])
                ->setDescription($data['description']);

            $this->entityManager->persist($entity);
            $map[$data['slug']] = $entity;
            $created++;
        }

        $this->entityManager->flush();
        $io->info(sprintf('Categories: %d created, %d skipped.', $created, $skipped));

        return $map;
    }

    /**
     * Seed the 48 professions (3 tiers per 16 categories).
     */
    private function seedProfessions(SymfonyStyle $io, array $categoryMap): int
    {
        $professions = [
            // Combat
            ['slug' => 'fighter', 'name' => 'Fighter', 'tier' => 1, 'category' => 'combat', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'A raw recruit who has taken their first steps into hand-to-hand combat.'],
            ['slug' => 'gladiator', 'name' => 'Gladiator', 'tier' => 2, 'category' => 'combat', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'A seasoned arena combatant who has survived countless bouts.'],
            ['slug' => 'titan-breaker', 'name' => 'Titan Breaker', 'tier' => 3, 'category' => 'combat', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'An unstoppable force of martial devastation whose fists shatter defenses like siege engines.'],
            // Running
            ['slug' => 'rogue', 'name' => 'Rogue', 'tier' => 1, 'category' => 'running', 'primaryStat' => 'dex', 'secondaryStat' => 'con', 'description' => 'A quick-footed newcomer learning the rhythm of the road.'],
            ['slug' => 'pathfinder', 'name' => 'Pathfinder', 'tier' => 2, 'category' => 'running', 'primaryStat' => 'dex', 'secondaryStat' => 'con', 'description' => 'An experienced trail runner who reads terrain like a map.'],
            ['slug' => 'wind-rider', 'name' => 'Wind Rider', 'tier' => 3, 'category' => 'running', 'primaryStat' => 'dex', 'secondaryStat' => 'con', 'description' => 'A transcendent master of speed who moves so fast they seem to ride the wind itself.'],
            // Walking
            ['slug' => 'wanderer', 'name' => 'Wanderer', 'tier' => 1, 'category' => 'walking', 'primaryStat' => 'con', 'secondaryStat' => 'dex', 'description' => 'A curious traveler building stamina through daily walks and gentle hikes.'],
            ['slug' => 'pilgrim', 'name' => 'Pilgrim', 'tier' => 2, 'category' => 'walking', 'primaryStat' => 'con', 'secondaryStat' => 'dex', 'description' => 'A hardened journeyman who has crossed mountain passes without rest.'],
            ['slug' => 'eternal-strider', 'name' => 'Eternal Strider', 'tier' => 3, 'category' => 'walking', 'primaryStat' => 'con', 'secondaryStat' => 'dex', 'description' => 'A mythic wayfarer said to have walked since the dawn of the world.'],
            // Cycling
            ['slug' => 'rider', 'name' => 'Rider', 'tier' => 1, 'category' => 'cycling', 'primaryStat' => 'con', 'secondaryStat' => 'str', 'description' => 'A novice cyclist learning to harness the power of the pedal.'],
            ['slug' => 'dark-rider', 'name' => 'Dark Rider', 'tier' => 2, 'category' => 'cycling', 'primaryStat' => 'con', 'secondaryStat' => 'str', 'description' => 'A relentless mounted warrior who dominates long roads and steep climbs.'],
            ['slug' => 'iron-cavalier', 'name' => 'Iron Cavalier', 'tier' => 3, 'category' => 'cycling', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'A master of the iron steed who rides with terrifying speed over any terrain.'],
            // Swimming
            ['slug' => 'tide-warden', 'name' => 'Tide Warden', 'tier' => 1, 'category' => 'swimming', 'primaryStat' => 'con', 'secondaryStat' => 'str', 'description' => 'A beginner who has entered the water and begun learning to move with the current.'],
            ['slug' => 'depth-walker', 'name' => 'Depth Walker', 'tier' => 2, 'category' => 'swimming', 'primaryStat' => 'con', 'secondaryStat' => 'str', 'description' => 'A confident swimmer who glides through water with efficient, powerful strokes.'],
            ['slug' => 'abyssal-lord', 'name' => 'Abyssal Lord', 'tier' => 3, 'category' => 'swimming', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'A sovereign of the deep who moves through water as naturally as breathing air.'],
            // Strength
            ['slug' => 'brawler', 'name' => 'Brawler', 'tier' => 1, 'category' => 'strength', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'A raw powerhouse just beginning to channel brute force into structured lifts.'],
            ['slug' => 'destroyer', 'name' => 'Destroyer', 'tier' => 2, 'category' => 'strength', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'A fearsome lifter who shatters personal records like enemy fortifications.'],
            ['slug' => 'tyrant', 'name' => 'Tyrant', 'tier' => 3, 'category' => 'strength', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'An absolute sovereign of raw power whose lifts defy belief.'],
            // Flexibility
            ['slug' => 'monk', 'name' => 'Monk', 'tier' => 1, 'category' => 'flexibility', 'primaryStat' => 'dex', 'secondaryStat' => 'con', 'description' => 'A student of body and breath, beginning the practice of flexibility and balance.'],
            ['slug' => 'bladedancer', 'name' => 'Bladedancer', 'tier' => 2, 'category' => 'flexibility', 'primaryStat' => 'dex', 'secondaryStat' => 'con', 'description' => 'A graceful practitioner whose movements flow with the precision of a blade.'],
            ['slug' => 'phantom-dancer', 'name' => 'Phantom Dancer', 'tier' => 3, 'category' => 'flexibility', 'primaryStat' => 'dex', 'secondaryStat' => 'con', 'description' => 'A transcendent master who moves as though unbound by joints or gravity.'],
            // Cardio
            ['slug' => 'scout', 'name' => 'Scout', 'tier' => 1, 'category' => 'cardio', 'primaryStat' => 'con', 'secondaryStat' => 'dex', 'description' => 'An energetic recruit building cardiovascular base through interval work.'],
            ['slug' => 'storm-chaser', 'name' => 'Storm Chaser', 'tier' => 2, 'category' => 'cardio', 'primaryStat' => 'con', 'secondaryStat' => 'dex', 'description' => 'A hardened endurance athlete who thrives in high-intensity training chaos.'],
            ['slug' => 'tempest-warden', 'name' => 'Tempest Warden', 'tier' => 3, 'category' => 'cardio', 'primaryStat' => 'con', 'secondaryStat' => 'dex', 'description' => 'A living engine of metabolic fury whose cardio capacity borders on supernatural.'],
            // Dance
            ['slug' => 'minstrel', 'name' => 'Minstrel', 'tier' => 1, 'category' => 'dance', 'primaryStat' => 'dex', 'secondaryStat' => 'con', 'description' => 'A spirited beginner discovering the joy of movement through rhythm.'],
            ['slug' => 'swordsinger', 'name' => 'Swordsinger', 'tier' => 2, 'category' => 'dance', 'primaryStat' => 'dex', 'secondaryStat' => 'con', 'description' => 'A disciplined performer whose every step carries intent and whose rhythm never breaks.'],
            ['slug' => 'celestial-bard', 'name' => 'Celestial Bard', 'tier' => 3, 'category' => 'dance', 'primaryStat' => 'dex', 'secondaryStat' => 'con', 'description' => 'A legendary performer whose dance transcends physicality.'],
            // Winter Sports
            ['slug' => 'frost-scout', 'name' => 'Frost Scout', 'tier' => 1, 'category' => 'winter_sports', 'primaryStat' => 'dex', 'secondaryStat' => 'con', 'description' => 'A bold newcomer who has braved the cold and begun learning snow and ice.'],
            ['slug' => 'ice-warden', 'name' => 'Ice Warden', 'tier' => 2, 'category' => 'winter_sports', 'primaryStat' => 'dex', 'secondaryStat' => 'str', 'description' => 'A skilled winter athlete who carves through powder and ice with confident technique.'],
            ['slug' => 'boreal-sovereign', 'name' => 'Boreal Sovereign', 'tier' => 3, 'category' => 'winter_sports', 'primaryStat' => 'dex', 'secondaryStat' => 'str', 'description' => 'An untouchable master of frozen terrain whose speed on ice defies natural law.'],
            // Racquet Sports
            ['slug' => 'duelist', 'name' => 'Duelist', 'tier' => 1, 'category' => 'racquet_sports', 'primaryStat' => 'dex', 'secondaryStat' => 'str', 'description' => 'A sharp-eyed beginner learning racquet control and footwork.'],
            ['slug' => 'treasure-hunter', 'name' => 'Treasure Hunter', 'tier' => 2, 'category' => 'racquet_sports', 'primaryStat' => 'dex', 'secondaryStat' => 'str', 'description' => 'A cunning court tactician who finds openings where none seem to exist.'],
            ['slug' => 'phantom-striker', 'name' => 'Phantom Striker', 'tier' => 3, 'category' => 'racquet_sports', 'primaryStat' => 'dex', 'secondaryStat' => 'str', 'description' => 'A legendary duelist whose racquet moves faster than the eye can follow.'],
            // Team Sports
            ['slug' => 'squire', 'name' => 'Squire', 'tier' => 1, 'category' => 'team_sports', 'primaryStat' => 'str', 'secondaryStat' => 'dex', 'description' => 'A fresh recruit learning to work within a team.'],
            ['slug' => 'warlord', 'name' => 'Warlord', 'tier' => 2, 'category' => 'team_sports', 'primaryStat' => 'str', 'secondaryStat' => 'dex', 'description' => 'A commanding presence who reads the flow of the game and directs teammates.'],
            ['slug' => 'grand-marshal', 'name' => 'Grand Marshal', 'tier' => 3, 'category' => 'team_sports', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'A supreme battlefield commander whose presence elevates every teammate.'],
            // Water Sports
            ['slug' => 'deckhand', 'name' => 'Deckhand', 'tier' => 1, 'category' => 'water_sports', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'A brave newcomer who has taken to the water.'],
            ['slug' => 'sea-raider', 'name' => 'Sea Raider', 'tier' => 2, 'category' => 'water_sports', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'A hardened mariner who cuts through waves with fearless confidence.'],
            ['slug' => 'storm-sovereign', 'name' => 'Storm Sovereign', 'tier' => 3, 'category' => 'water_sports', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'A mythic ruler of the open water who sails into tempests.'],
            // Outdoor
            ['slug' => 'ranger', 'name' => 'Ranger', 'tier' => 1, 'category' => 'outdoor', 'primaryStat' => 'dex', 'secondaryStat' => 'str', 'description' => 'A nature-bound adventurer learning outdoor disciplines.'],
            ['slug' => 'hawkeye', 'name' => 'Hawkeye', 'tier' => 2, 'category' => 'outdoor', 'primaryStat' => 'dex', 'secondaryStat' => 'str', 'description' => 'A sharp-eyed wilderness expert whose aim is deadly.'],
            ['slug' => 'silver-ranger', 'name' => 'Silver Ranger', 'tier' => 3, 'category' => 'outdoor', 'primaryStat' => 'dex', 'secondaryStat' => 'str', 'description' => 'A legendary master of the wild whose precision borders on the mythical.'],
            // Mind & Body
            ['slug' => 'mystic', 'name' => 'Mystic', 'tier' => 1, 'category' => 'mind_body', 'primaryStat' => 'con', 'secondaryStat' => 'dex', 'description' => 'A seeker beginning the inward journey of mind and breath.'],
            ['slug' => 'prophet', 'name' => 'Prophet', 'tier' => 2, 'category' => 'mind_body', 'primaryStat' => 'con', 'secondaryStat' => 'dex', 'description' => 'An enlightened practitioner whose breath control unlocks reservoirs of calm.'],
            ['slug' => 'archmage', 'name' => 'Archmage', 'tier' => 3, 'category' => 'mind_body', 'primaryStat' => 'con', 'secondaryStat' => 'dex', 'description' => 'A transcendent sage who has achieved mastery over mind, breath, and spirit.'],
            // Other
            ['slug' => 'adventurer', 'name' => 'Adventurer', 'tier' => 1, 'category' => 'other', 'primaryStat' => 'con', 'secondaryStat' => 'str', 'description' => 'A free spirit approaching fitness through unconventional means.'],
            ['slug' => 'warsmith', 'name' => 'Warsmith', 'tier' => 2, 'category' => 'other', 'primaryStat' => 'con', 'secondaryStat' => 'str', 'description' => 'A resourceful innovator who forges strength from any activity.'],
            ['slug' => 'chaos-vanguard', 'name' => 'Chaos Vanguard', 'tier' => 3, 'category' => 'other', 'primaryStat' => 'str', 'secondaryStat' => 'con', 'description' => 'A fearless pioneer whose mastery of unconventional training defies classification.'],
        ];

        $repo = $this->entityManager->getRepository(Profession::class);
        $created = 0;
        $skipped = 0;

        foreach ($professions as $data) {
            $existing = $repo->findOneBy(['slug' => $data['slug']]);
            if ($existing !== null) {
                $skipped++;
                continue;
            }

            $entity = new Profession();
            $entity->setSlug($data['slug'])
                ->setName($data['name'])
                ->setTier($data['tier'])
                ->setDescription($data['description'])
                ->setPrimaryStat(StatType::from($data['primaryStat']))
                ->setSecondaryStat(StatType::from($data['secondaryStat']))
                ->setCategory($categoryMap[$data['category']]);

            $this->entityManager->persist($entity);
            $created++;
        }

        $this->entityManager->flush();
        $io->info(sprintf('Professions: %d created, %d skipped.', $created, $skipped));

        return $created + $skipped;
    }

    /**
     * Seed all 99 activity types with platform support info.
     */
    private function seedActivityTypes(SymfonyStyle $io, array $categoryMap): int
    {
        $activityTypes = $this->getActivityTypeData();

        $repo = $this->entityManager->getRepository(ActivityType::class);
        $created = 0;
        $skipped = 0;

        foreach ($activityTypes as $data) {
            $existing = $repo->findOneBy(['slug' => $data['slug']]);
            if ($existing !== null) {
                $skipped++;
                continue;
            }

            $entity = new ActivityType();
            $entity->setSlug($data['slug'])
                ->setName($data['name'])
                ->setFlutterEnum($data['flutterEnum'])
                ->setIosNative($data['iosNative'])
                ->setAndroidNative($data['androidNative'])
                ->setPlatformSupport($data['platformSupport'])
                ->setFallbackSlug($data['fallbackSlug'])
                ->setCategory($categoryMap[$data['category']]);

            $this->entityManager->persist($entity);
            $created++;
        }

        $this->entityManager->flush();
        $io->info(sprintf('Activity Types: %d created, %d skipped.', $created, $skipped));

        return $created + $skipped;
    }

    /**
     * Returns the complete list of 99 activity types with platform mapping data.
     *
     * @return array<int, array{slug: string, name: string, flutterEnum: string, iosNative: ?string, androidNative: ?string, platformSupport: string, fallbackSlug: ?string, category: string}>
     */
    private function getActivityTypeData(): array
    {
        return [
            // Group 1: Universal (both iOS and Android) -- 47 types
            ['slug' => 'american_football', 'name' => 'American Football', 'flutterEnum' => 'AMERICAN_FOOTBALL', 'iosNative' => 'americanFootball', 'androidNative' => 'EXERCISE_TYPE_FOOTBALL_AMERICAN', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'team_sports'],
            ['slug' => 'archery', 'name' => 'Archery', 'flutterEnum' => 'ARCHERY', 'iosNative' => 'archery', 'androidNative' => 'EXERCISE_TYPE_ARCHERY', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'outdoor'],
            ['slug' => 'australian_football', 'name' => 'Australian Football', 'flutterEnum' => 'AUSTRALIAN_FOOTBALL', 'iosNative' => 'australianFootball', 'androidNative' => 'EXERCISE_TYPE_FOOTBALL_AUSTRALIAN', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'team_sports'],
            ['slug' => 'badminton', 'name' => 'Badminton', 'flutterEnum' => 'BADMINTON', 'iosNative' => 'badminton', 'androidNative' => 'EXERCISE_TYPE_BADMINTON', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'racquet_sports'],
            ['slug' => 'baseball', 'name' => 'Baseball', 'flutterEnum' => 'BASEBALL', 'iosNative' => 'baseball', 'androidNative' => 'EXERCISE_TYPE_BASEBALL', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'team_sports'],
            ['slug' => 'basketball', 'name' => 'Basketball', 'flutterEnum' => 'BASKETBALL', 'iosNative' => 'basketball', 'androidNative' => 'EXERCISE_TYPE_BASKETBALL', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'team_sports'],
            ['slug' => 'biking', 'name' => 'Biking', 'flutterEnum' => 'BIKING', 'iosNative' => 'cycling', 'androidNative' => 'EXERCISE_TYPE_BIKING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'cycling'],
            ['slug' => 'boxing', 'name' => 'Boxing', 'flutterEnum' => 'BOXING', 'iosNative' => 'boxing', 'androidNative' => 'EXERCISE_TYPE_BOXING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'combat'],
            ['slug' => 'cricket', 'name' => 'Cricket', 'flutterEnum' => 'CRICKET', 'iosNative' => 'cricket', 'androidNative' => 'EXERCISE_TYPE_CRICKET', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'team_sports'],
            ['slug' => 'cross_country_skiing', 'name' => 'Cross-Country Skiing', 'flutterEnum' => 'CROSS_COUNTRY_SKIING', 'iosNative' => 'crossCountrySkiing', 'androidNative' => 'EXERCISE_TYPE_CROSS_COUNTRY_SKIING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'winter_sports'],
            ['slug' => 'elliptical', 'name' => 'Elliptical', 'flutterEnum' => 'ELLIPTICAL', 'iosNative' => 'elliptical', 'androidNative' => 'EXERCISE_TYPE_ELLIPTICAL', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'cardio'],
            ['slug' => 'fencing', 'name' => 'Fencing', 'flutterEnum' => 'FENCING', 'iosNative' => 'fencing', 'androidNative' => 'EXERCISE_TYPE_FENCING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'combat'],
            ['slug' => 'golf', 'name' => 'Golf', 'flutterEnum' => 'GOLF', 'iosNative' => 'golf', 'androidNative' => 'EXERCISE_TYPE_GOLF', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'outdoor'],
            ['slug' => 'gymnastics', 'name' => 'Gymnastics', 'flutterEnum' => 'GYMNASTICS', 'iosNative' => 'gymnastics', 'androidNative' => 'EXERCISE_TYPE_GYMNASTICS', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'flexibility'],
            ['slug' => 'handball', 'name' => 'Handball', 'flutterEnum' => 'HANDBALL', 'iosNative' => 'handball', 'androidNative' => 'EXERCISE_TYPE_HANDBALL', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'team_sports'],
            ['slug' => 'high_intensity_interval_training', 'name' => 'HIIT', 'flutterEnum' => 'HIGH_INTENSITY_INTERVAL_TRAINING', 'iosNative' => 'highIntensityIntervalTraining', 'androidNative' => 'EXERCISE_TYPE_HIGH_INTENSITY_INTERVAL_TRAINING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'cardio'],
            ['slug' => 'hiking', 'name' => 'Hiking', 'flutterEnum' => 'HIKING', 'iosNative' => 'hiking', 'androidNative' => 'EXERCISE_TYPE_HIKING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'walking'],
            ['slug' => 'hockey', 'name' => 'Hockey', 'flutterEnum' => 'HOCKEY', 'iosNative' => 'hockey', 'androidNative' => 'EXERCISE_TYPE_ICE_HOCKEY', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'team_sports'],
            ['slug' => 'jump_rope', 'name' => 'Jump Rope', 'flutterEnum' => 'JUMP_ROPE', 'iosNative' => 'jumpRope', 'androidNative' => 'EXERCISE_TYPE_JUMPING_ROPE', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'cardio'],
            ['slug' => 'kickboxing', 'name' => 'Kickboxing', 'flutterEnum' => 'KICKBOXING', 'iosNative' => 'kickboxing', 'androidNative' => 'EXERCISE_TYPE_KICKBOXING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'combat'],
            ['slug' => 'martial_arts', 'name' => 'Martial Arts', 'flutterEnum' => 'MARTIAL_ARTS', 'iosNative' => 'martialArts', 'androidNative' => 'EXERCISE_TYPE_MARTIAL_ARTS', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'combat'],
            ['slug' => 'pilates', 'name' => 'Pilates', 'flutterEnum' => 'PILATES', 'iosNative' => 'pilates', 'androidNative' => 'EXERCISE_TYPE_PILATES', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'flexibility'],
            ['slug' => 'racquetball', 'name' => 'Racquetball', 'flutterEnum' => 'RACQUETBALL', 'iosNative' => 'racquetball', 'androidNative' => 'EXERCISE_TYPE_RACQUETBALL', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'racquet_sports'],
            ['slug' => 'rowing', 'name' => 'Rowing', 'flutterEnum' => 'ROWING', 'iosNative' => 'rowing', 'androidNative' => 'EXERCISE_TYPE_ROWING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'water_sports'],
            ['slug' => 'rugby', 'name' => 'Rugby', 'flutterEnum' => 'RUGBY', 'iosNative' => 'rugby', 'androidNative' => 'EXERCISE_TYPE_RUGBY', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'team_sports'],
            ['slug' => 'running', 'name' => 'Running', 'flutterEnum' => 'RUNNING', 'iosNative' => 'running', 'androidNative' => 'EXERCISE_TYPE_RUNNING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'running'],
            ['slug' => 'sailing', 'name' => 'Sailing', 'flutterEnum' => 'SAILING', 'iosNative' => 'sailing', 'androidNative' => 'EXERCISE_TYPE_SAILING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'water_sports'],
            ['slug' => 'skating', 'name' => 'Skating', 'flutterEnum' => 'SKATING', 'iosNative' => 'skating', 'androidNative' => 'EXERCISE_TYPE_SKATING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'winter_sports'],
            ['slug' => 'snowboarding', 'name' => 'Snowboarding', 'flutterEnum' => 'SNOWBOARDING', 'iosNative' => 'snowboarding', 'androidNative' => 'EXERCISE_TYPE_SNOWBOARDING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'winter_sports'],
            ['slug' => 'soccer', 'name' => 'Soccer', 'flutterEnum' => 'SOCCER', 'iosNative' => 'soccer', 'androidNative' => 'EXERCISE_TYPE_SOCCER', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'team_sports'],
            ['slug' => 'softball', 'name' => 'Softball', 'flutterEnum' => 'SOFTBALL', 'iosNative' => 'softball', 'androidNative' => 'EXERCISE_TYPE_SOFTBALL', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'team_sports'],
            ['slug' => 'squash', 'name' => 'Squash', 'flutterEnum' => 'SQUASH', 'iosNative' => 'squash', 'androidNative' => 'EXERCISE_TYPE_SQUASH', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'racquet_sports'],
            ['slug' => 'stair_climbing', 'name' => 'Stair Climbing', 'flutterEnum' => 'STAIR_CLIMBING', 'iosNative' => 'stairClimbing', 'androidNative' => 'EXERCISE_TYPE_STAIR_CLIMBING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'walking'],
            ['slug' => 'swimming', 'name' => 'Swimming', 'flutterEnum' => 'SWIMMING', 'iosNative' => 'swimming', 'androidNative' => 'EXERCISE_TYPE_SWIMMING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'swimming'],
            ['slug' => 'table_tennis', 'name' => 'Table Tennis', 'flutterEnum' => 'TABLE_TENNIS', 'iosNative' => 'tableTennis', 'androidNative' => 'EXERCISE_TYPE_TABLE_TENNIS', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'racquet_sports'],
            ['slug' => 'tennis', 'name' => 'Tennis', 'flutterEnum' => 'TENNIS', 'iosNative' => 'tennis', 'androidNative' => 'EXERCISE_TYPE_TENNIS', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'racquet_sports'],
            ['slug' => 'volleyball', 'name' => 'Volleyball', 'flutterEnum' => 'VOLLEYBALL', 'iosNative' => 'volleyball', 'androidNative' => 'EXERCISE_TYPE_VOLLEYBALL', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'team_sports'],
            ['slug' => 'walking', 'name' => 'Walking', 'flutterEnum' => 'WALKING', 'iosNative' => 'walking', 'androidNative' => 'EXERCISE_TYPE_WALKING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'walking'],
            ['slug' => 'water_polo', 'name' => 'Water Polo', 'flutterEnum' => 'WATER_POLO', 'iosNative' => 'waterPolo', 'androidNative' => 'EXERCISE_TYPE_WATER_POLO', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'swimming'],
            ['slug' => 'yoga', 'name' => 'Yoga', 'flutterEnum' => 'YOGA', 'iosNative' => 'yoga', 'androidNative' => 'EXERCISE_TYPE_YOGA', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'flexibility'],
            ['slug' => 'bowling', 'name' => 'Bowling', 'flutterEnum' => 'BOWLING', 'iosNative' => 'bowling', 'androidNative' => 'EXERCISE_TYPE_BOWLING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'other'],
            ['slug' => 'climbing', 'name' => 'Climbing', 'flutterEnum' => 'CLIMBING', 'iosNative' => 'climbing', 'androidNative' => 'EXERCISE_TYPE_ROCK_CLIMBING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'outdoor'],
            ['slug' => 'wrestling', 'name' => 'Wrestling', 'flutterEnum' => 'WRESTLING', 'iosNative' => 'wrestling', 'androidNative' => 'EXERCISE_TYPE_WRESTLING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'combat'],
            ['slug' => 'surfing', 'name' => 'Surfing', 'flutterEnum' => 'SURFING', 'iosNative' => 'surfingSports', 'androidNative' => 'EXERCISE_TYPE_SURFING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'water_sports'],
            ['slug' => 'tai_chi', 'name' => 'Tai Chi', 'flutterEnum' => 'TAI_CHI', 'iosNative' => 'taiChi', 'androidNative' => 'EXERCISE_TYPE_TAI_CHI', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'flexibility'],
            ['slug' => 'dancing', 'name' => 'Dancing', 'flutterEnum' => 'DANCING', 'iosNative' => 'dance', 'androidNative' => 'EXERCISE_TYPE_DANCING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'dance'],
            ['slug' => 'downhill_skiing', 'name' => 'Downhill Skiing', 'flutterEnum' => 'DOWNHILL_SKIING', 'iosNative' => 'downhillSkiing', 'androidNative' => 'EXERCISE_TYPE_SKIING', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'winter_sports'],
            ['slug' => 'other', 'name' => 'Other', 'flutterEnum' => 'OTHER', 'iosNative' => 'other', 'androidNative' => 'EXERCISE_TYPE_OTHER_WORKOUT', 'platformSupport' => 'universal', 'fallbackSlug' => null, 'category' => 'other'],

            // Group 2: iOS Only (with Android fallback) -- 32 types
            ['slug' => 'barre', 'name' => 'Barre', 'flutterEnum' => 'BARRE', 'iosNative' => 'barre', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'pilates', 'category' => 'flexibility'],
            ['slug' => 'cardio_dance', 'name' => 'Cardio Dance', 'flutterEnum' => 'CARDIO_DANCE', 'iosNative' => 'cardioDance', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'dancing', 'category' => 'dance'],
            ['slug' => 'cooldown', 'name' => 'Cooldown', 'flutterEnum' => 'COOLDOWN', 'iosNative' => 'cooldown', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'other', 'category' => 'mind_body'],
            ['slug' => 'core_training', 'name' => 'Core Training', 'flutterEnum' => 'CORE_TRAINING', 'iosNative' => 'coreTraining', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'strength_training', 'category' => 'strength'],
            ['slug' => 'cross_training', 'name' => 'Cross Training', 'flutterEnum' => 'CROSS_TRAINING', 'iosNative' => 'crossTraining', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'high_intensity_interval_training', 'category' => 'cardio'],
            ['slug' => 'curling', 'name' => 'Curling', 'flutterEnum' => 'CURLING', 'iosNative' => 'curling', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'other', 'category' => 'winter_sports'],
            ['slug' => 'disc_sports', 'name' => 'Disc Sports', 'flutterEnum' => 'DISC_SPORTS', 'iosNative' => 'discSports', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'other', 'category' => 'other'],
            ['slug' => 'equestrian_sports', 'name' => 'Equestrian Sports', 'flutterEnum' => 'EQUESTRIAN_SPORTS', 'iosNative' => 'equestrianSports', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'other', 'category' => 'outdoor'],
            ['slug' => 'fishing', 'name' => 'Fishing', 'flutterEnum' => 'FISHING', 'iosNative' => 'fishing', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'other', 'category' => 'outdoor'],
            ['slug' => 'fitness_gaming', 'name' => 'Fitness Gaming', 'flutterEnum' => 'FITNESS_GAMING', 'iosNative' => 'fitnessGaming', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'other', 'category' => 'other'],
            ['slug' => 'flexibility', 'name' => 'Flexibility', 'flutterEnum' => 'FLEXIBILITY', 'iosNative' => 'flexibility', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'yoga', 'category' => 'flexibility'],
            ['slug' => 'functional_strength_training', 'name' => 'Functional Strength Training', 'flutterEnum' => 'FUNCTIONAL_STRENGTH_TRAINING', 'iosNative' => 'functionalStrengthTraining', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'strength_training', 'category' => 'strength'],
            ['slug' => 'hand_cycling', 'name' => 'Hand Cycling', 'flutterEnum' => 'HAND_CYCLING', 'iosNative' => 'handCycling', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'biking', 'category' => 'cycling'],
            ['slug' => 'hunting', 'name' => 'Hunting', 'flutterEnum' => 'HUNTING', 'iosNative' => 'hunting', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'hiking', 'category' => 'outdoor'],
            ['slug' => 'lacrosse', 'name' => 'Lacrosse', 'flutterEnum' => 'LACROSSE', 'iosNative' => 'lacrosse', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'other', 'category' => 'team_sports'],
            ['slug' => 'mind_and_body', 'name' => 'Mind and Body', 'flutterEnum' => 'MIND_AND_BODY', 'iosNative' => 'mindAndBody', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'yoga', 'category' => 'mind_body'],
            ['slug' => 'mixed_cardio', 'name' => 'Mixed Cardio', 'flutterEnum' => 'MIXED_CARDIO', 'iosNative' => 'mixedCardio', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'high_intensity_interval_training', 'category' => 'cardio'],
            ['slug' => 'paddle_sports', 'name' => 'Paddle Sports', 'flutterEnum' => 'PADDLE_SPORTS', 'iosNative' => 'paddleSports', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'rowing', 'category' => 'water_sports'],
            ['slug' => 'pickleball', 'name' => 'Pickleball', 'flutterEnum' => 'PICKLEBALL', 'iosNative' => 'pickleball', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'badminton', 'category' => 'racquet_sports'],
            ['slug' => 'play', 'name' => 'Play', 'flutterEnum' => 'PLAY', 'iosNative' => 'play', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'other', 'category' => 'other'],
            ['slug' => 'preparation_and_recovery', 'name' => 'Preparation and Recovery', 'flutterEnum' => 'PREPARATION_AND_RECOVERY', 'iosNative' => 'preparationAndRecovery', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'other', 'category' => 'mind_body'],
            ['slug' => 'snow_sports', 'name' => 'Snow Sports', 'flutterEnum' => 'SNOW_SPORTS', 'iosNative' => 'snowSports', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'downhill_skiing', 'category' => 'winter_sports'],
            ['slug' => 'social_dance', 'name' => 'Social Dance', 'flutterEnum' => 'SOCIAL_DANCE', 'iosNative' => 'socialDance', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'dancing', 'category' => 'dance'],
            ['slug' => 'stairs', 'name' => 'Stairs', 'flutterEnum' => 'STAIRS', 'iosNative' => 'stairs', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'stair_climbing', 'category' => 'walking'],
            ['slug' => 'step_training', 'name' => 'Step Training', 'flutterEnum' => 'STEP_TRAINING', 'iosNative' => 'stepTraining', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'high_intensity_interval_training', 'category' => 'cardio'],
            ['slug' => 'track_and_field', 'name' => 'Track and Field', 'flutterEnum' => 'TRACK_AND_FIELD', 'iosNative' => 'trackAndField', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'running', 'category' => 'running'],
            ['slug' => 'traditional_strength_training', 'name' => 'Traditional Strength Training', 'flutterEnum' => 'TRADITIONAL_STRENGTH_TRAINING', 'iosNative' => 'traditionalStrengthTraining', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'strength_training', 'category' => 'strength'],
            ['slug' => 'underwater_diving', 'name' => 'Underwater Diving', 'flutterEnum' => 'UNDERWATER_DIVING', 'iosNative' => 'underwaterDiving', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'scuba_diving', 'category' => 'water_sports'],
            ['slug' => 'water_fitness', 'name' => 'Water Fitness', 'flutterEnum' => 'WATER_FITNESS', 'iosNative' => 'waterFitness', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'swimming', 'category' => 'swimming'],
            ['slug' => 'water_sports', 'name' => 'Water Sports', 'flutterEnum' => 'WATER_SPORTS', 'iosNative' => 'waterSports', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'swimming', 'category' => 'water_sports'],
            ['slug' => 'wheelchair_run_pace', 'name' => 'Wheelchair Run Pace', 'flutterEnum' => 'WHEELCHAIR_RUN_PACE', 'iosNative' => 'wheelchairRunPace', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'wheelchair', 'category' => 'other'],
            ['slug' => 'wheelchair_walk_pace', 'name' => 'Wheelchair Walk Pace', 'flutterEnum' => 'WHEELCHAIR_WALK_PACE', 'iosNative' => 'wheelchairWalkPace', 'androidNative' => null, 'platformSupport' => 'ios_only', 'fallbackSlug' => 'wheelchair', 'category' => 'other'],

            // Group 3: Android Only (with iOS fallback) -- 19 types
            ['slug' => 'biking_stationary', 'name' => 'Stationary Biking', 'flutterEnum' => 'BIKING_STATIONARY', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_BIKING_STATIONARY', 'platformSupport' => 'android_only', 'fallbackSlug' => 'biking', 'category' => 'cycling'],
            ['slug' => 'calisthenics', 'name' => 'Calisthenics', 'flutterEnum' => 'CALISTHENICS', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_CALISTHENICS', 'platformSupport' => 'android_only', 'fallbackSlug' => 'functional_strength_training', 'category' => 'strength'],
            ['slug' => 'frisbee_disc', 'name' => 'Frisbee Disc', 'flutterEnum' => 'FRISBEE_DISC', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_FRISBEE_DISC', 'platformSupport' => 'android_only', 'fallbackSlug' => 'disc_sports', 'category' => 'other'],
            ['slug' => 'guided_breathing', 'name' => 'Guided Breathing', 'flutterEnum' => 'GUIDED_BREATHING', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_GUIDED_BREATHING', 'platformSupport' => 'android_only', 'fallbackSlug' => 'mind_and_body', 'category' => 'mind_body'],
            ['slug' => 'ice_skating', 'name' => 'Ice Skating', 'flutterEnum' => 'ICE_SKATING', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_ICE_SKATING', 'platformSupport' => 'android_only', 'fallbackSlug' => 'skating', 'category' => 'winter_sports'],
            ['slug' => 'paragliding', 'name' => 'Paragliding', 'flutterEnum' => 'PARAGLIDING', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_PARAGLIDING', 'platformSupport' => 'android_only', 'fallbackSlug' => 'other', 'category' => 'outdoor'],
            ['slug' => 'rock_climbing', 'name' => 'Rock Climbing', 'flutterEnum' => 'ROCK_CLIMBING', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_ROCK_CLIMBING', 'platformSupport' => 'android_only', 'fallbackSlug' => 'climbing', 'category' => 'outdoor'],
            ['slug' => 'rowing_machine', 'name' => 'Rowing Machine', 'flutterEnum' => 'ROWING_MACHINE', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_ROWING_MACHINE', 'platformSupport' => 'android_only', 'fallbackSlug' => 'rowing', 'category' => 'water_sports'],
            ['slug' => 'running_treadmill', 'name' => 'Treadmill Running', 'flutterEnum' => 'RUNNING_TREADMILL', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_RUNNING_TREADMILL', 'platformSupport' => 'android_only', 'fallbackSlug' => 'running', 'category' => 'running'],
            ['slug' => 'scuba_diving', 'name' => 'Scuba Diving', 'flutterEnum' => 'SCUBA_DIVING', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_SCUBA_DIVING', 'platformSupport' => 'android_only', 'fallbackSlug' => 'underwater_diving', 'category' => 'water_sports'],
            ['slug' => 'skiing', 'name' => 'Skiing', 'flutterEnum' => 'SKIING', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_SKIING', 'platformSupport' => 'android_only', 'fallbackSlug' => 'downhill_skiing', 'category' => 'winter_sports'],
            ['slug' => 'snowshoeing', 'name' => 'Snowshoeing', 'flutterEnum' => 'SNOWSHOEING', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_SNOWSHOEING', 'platformSupport' => 'android_only', 'fallbackSlug' => 'hiking', 'category' => 'winter_sports'],
            ['slug' => 'stair_climbing_machine', 'name' => 'Stair Climbing Machine', 'flutterEnum' => 'STAIR_CLIMBING_MACHINE', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_STAIR_CLIMBING_MACHINE', 'platformSupport' => 'android_only', 'fallbackSlug' => 'stair_climbing', 'category' => 'walking'],
            ['slug' => 'strength_training', 'name' => 'Strength Training', 'flutterEnum' => 'STRENGTH_TRAINING', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_STRENGTH_TRAINING', 'platformSupport' => 'android_only', 'fallbackSlug' => 'traditional_strength_training', 'category' => 'strength'],
            ['slug' => 'swimming_open_water', 'name' => 'Open Water Swimming', 'flutterEnum' => 'SWIMMING_OPEN_WATER', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_SWIMMING_OPEN_WATER', 'platformSupport' => 'android_only', 'fallbackSlug' => 'swimming', 'category' => 'swimming'],
            ['slug' => 'swimming_pool', 'name' => 'Pool Swimming', 'flutterEnum' => 'SWIMMING_POOL', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_SWIMMING_POOL', 'platformSupport' => 'android_only', 'fallbackSlug' => 'swimming', 'category' => 'swimming'],
            ['slug' => 'walking_treadmill', 'name' => 'Treadmill Walking', 'flutterEnum' => 'WALKING_TREADMILL', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_WALKING_TREADMILL', 'platformSupport' => 'android_only', 'fallbackSlug' => 'walking', 'category' => 'walking'],
            ['slug' => 'weightlifting', 'name' => 'Weightlifting', 'flutterEnum' => 'WEIGHTLIFTING', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_WEIGHTLIFTING', 'platformSupport' => 'android_only', 'fallbackSlug' => 'traditional_strength_training', 'category' => 'strength'],
            ['slug' => 'wheelchair', 'name' => 'Wheelchair', 'flutterEnum' => 'WHEELCHAIR', 'iosNative' => null, 'androidNative' => 'EXERCISE_TYPE_WHEELCHAIR', 'platformSupport' => 'android_only', 'fallbackSlug' => 'wheelchair_walk_pace', 'category' => 'other'],
        ];
    }
}
