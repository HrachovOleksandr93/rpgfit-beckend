<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Battle\Service\BattleResultCalculator;
use App\Application\Character\Service\LevelingService;
use App\Domain\Inventory\Entity\ItemCatalog;
use App\Domain\Inventory\Entity\UserInventory;
use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Inventory\Enum\ItemType;
use App\Domain\Mob\Entity\Mob;
use App\Domain\Shared\Enum\Realm;
use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\Inventory\Repository\ItemCatalogRepository;
use App\Infrastructure\Inventory\Repository\UserInventoryRepository;
use App\Infrastructure\Mob\Repository\MobRepository;
use App\Infrastructure\Skill\Repository\SkillRepository;
use App\Infrastructure\Skill\Repository\UserSkillRepository;
use App\Infrastructure\Workout\Repository\ExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Verifies BUSINESS_LOGIC §12 — artifact realm-match damage multiplier.
 *
 * Three scenarios:
 *  - match:    artifact.realm == mob.realm  -> +40% damage
 *  - no-match: artifact.realm != mob.realm  -> no change
 *  - neutral:  artifact.realm == null       -> no change
 *
 * We exercise the public multiplier helper directly plus the repository-
 * driven check via a mocked UserInventoryRepository.
 */
class ArtifactRealmMultiplierTest extends TestCase
{
    public function testApplyRealmMatchMultiplierMultipliesDamageBy14(): void
    {
        $calculator = $this->buildCalculatorWithInventory([]);

        $this->assertSame(140, $calculator->applyRealmMatchMultiplier(100));
        $this->assertSame(0, $calculator->applyRealmMatchMultiplier(0));
        $this->assertSame(14, $calculator->applyRealmMatchMultiplier(10));
        // Rounding: 57 * 1.4 = 79.8 -> 80
        $this->assertSame(80, $calculator->applyRealmMatchMultiplier(57));
    }

    public function testRealmMatchTriggeredWhenEquippedArtifactSharesRealm(): void
    {
        $olympusItem = $this->makeItem(Realm::Olympus);
        $inventory = $this->makeInventory($olympusItem);

        $calculator = $this->buildCalculatorWithInventory([$inventory]);
        $user = $this->createMock(User::class);

        $this->assertTrue($this->invokeRealmMatch($calculator, $user, Realm::Olympus));
    }

    public function testRealmMismatchDoesNotTrigger(): void
    {
        $asgardItem = $this->makeItem(Realm::Asgard);
        $inventory = $this->makeInventory($asgardItem);

        $calculator = $this->buildCalculatorWithInventory([$inventory]);
        $user = $this->createMock(User::class);

        $this->assertFalse($this->invokeRealmMatch($calculator, $user, Realm::Olympus));
    }

    public function testNeutralArtifactNeverTriggers(): void
    {
        $neutralItem = $this->makeItem(null); // unbound artifact
        $inventory = $this->makeInventory($neutralItem);

        $calculator = $this->buildCalculatorWithInventory([$inventory]);
        $user = $this->createMock(User::class);

        $this->assertFalse($this->invokeRealmMatch($calculator, $user, Realm::Olympus));
    }

    /**
     * Build a calculator with mocked collaborators. Only the
     * UserInventoryRepository is relevant for the realm-match code path.
     *
     * @param list<UserInventory> $equipped
     */
    private function buildCalculatorWithInventory(array $equipped): BattleResultCalculator
    {
        $inventoryRepo = $this->createMock(UserInventoryRepository::class);
        $inventoryRepo->method('findEquippedByUser')->willReturn($equipped);

        return new BattleResultCalculator(
            gameSettingRepository: $this->createMock(GameSettingRepository::class),
            characterStatsRepository: $this->createMock(CharacterStatsRepository::class),
            exerciseRepository: $this->createMock(ExerciseRepository::class),
            userInventoryRepository: $inventoryRepo,
            userSkillRepository: $this->createMock(UserSkillRepository::class),
            skillRepository: $this->createMock(SkillRepository::class),
            itemCatalogRepository: $this->createMock(ItemCatalogRepository::class),
            mobRepository: $this->createMock(MobRepository::class),
            levelingService: $this->createMock(LevelingService::class),
            experienceLogRepository: $this->createMock(ExperienceLogRepository::class),
            entityManager: $this->createMock(EntityManagerInterface::class),
        );
    }

    private function makeItem(?Realm $realm): ItemCatalog
    {
        $item = new ItemCatalog();
        $item->setSlug('test-' . bin2hex(random_bytes(3)))
            ->setName('Test Artifact')
            ->setItemType(ItemType::Equipment)
            ->setRarity(ItemRarity::Rare)
            ->setRealm($realm);

        return $item;
    }

    private function makeInventory(ItemCatalog $catalog): UserInventory
    {
        $inv = new UserInventory();
        $user = $this->createMock(User::class);
        $inv->setUser($user);
        $inv->setItemCatalog($catalog);
        $inv->setEquipped(true);

        return $inv;
    }

    /**
     * Invoke the private hasRealmBoundArtifactMatching method via reflection.
     */
    private function invokeRealmMatch(BattleResultCalculator $calculator, User $user, Realm $mobRealm): bool
    {
        $ref = new \ReflectionClass(BattleResultCalculator::class);
        $method = $ref->getMethod('hasRealmBoundArtifactMatching');
        $method->setAccessible(true);

        return (bool) $method->invoke($calculator, $user, $mobRealm);
    }
}
