<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Application\Inventory\Service\EquipmentService;
use App\Domain\Inventory\Entity\ItemCatalog;
use App\Domain\Inventory\Entity\UserInventory;
use App\Domain\Inventory\Enum\EquipmentSlot;
use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Inventory\Enum\ItemType;
use App\Domain\User\Entity\User;
use App\Infrastructure\Inventory\Repository\UserInventoryRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EquipmentService slot rules.
 *
 * Tests all equipment slot constraints: standard slot replacement, two-handed
 * weapon interactions, shield conflicts, ring/bracelet limits, and error cases.
 */
class EquipmentServiceTest extends TestCase
{
    private EquipmentService $service;
    private UserInventoryRepository&MockObject $repository;
    private User $user;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserInventoryRepository::class);
        $this->service = new EquipmentService($this->repository);

        $this->user = new User();
        $this->user->setLogin('test@rpgfit.com');
        $this->user->setPassword('hashed');
    }

    /** Create a catalog item with given properties for testing. */
    private function createCatalogItem(
        ItemType $type = ItemType::Equipment,
        ?EquipmentSlot $slot = null,
        bool $twoHanded = false,
    ): ItemCatalog {
        $item = new ItemCatalog();
        $item->setName('Test Item');
        $item->setSlug('test-item');
        $item->setItemType($type);
        $item->setRarity(ItemRarity::Common);
        $item->setSlot($slot);
        $item->setTwoHanded($twoHanded);

        return $item;
    }

    /** Create a UserInventory item linked to our test user. */
    private function createInventoryItem(ItemCatalog $catalogItem): UserInventory
    {
        $inv = new UserInventory();
        $inv->setUser($this->user);
        $inv->setItemCatalog($catalogItem);

        return $inv;
    }

    public function testEquipOneHandedWeapon(): void
    {
        $catalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Weapon, false);
        $inv = $this->createInventoryItem($catalog);

        // No existing weapons equipped
        $this->repository->method('findEquippedBySlot')->willReturn([]);
        $this->repository->expects($this->once())->method('save')->with($inv);

        $this->service->equip($this->user, $inv);

        $this->assertTrue($inv->isEquipped());
        $this->assertSame(EquipmentSlot::Weapon, $inv->getEquippedSlot());
    }

    public function testEquipTwoHandedWeaponRemovesShield(): void
    {
        $twoHandedCatalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Weapon, true);
        $twoHandedInv = $this->createInventoryItem($twoHandedCatalog);

        // Existing shield and weapon in slots
        $shieldCatalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Shield);
        $shieldInv = $this->createInventoryItem($shieldCatalog);
        $shieldInv->setEquipped(true);
        $shieldInv->setEquippedSlot(EquipmentSlot::Shield);

        $this->repository->method('findEquippedBySlot')
            ->willReturnCallback(function (User $user, EquipmentSlot $slot) use ($shieldInv) {
                if ($slot === EquipmentSlot::Weapon) {
                    return [];
                }
                if ($slot === EquipmentSlot::Shield) {
                    return [$shieldInv];
                }
                return [];
            });

        $savedItems = [];
        $this->repository->method('save')->willReturnCallback(function ($item) use (&$savedItems) {
            $savedItems[] = $item;
        });

        $this->service->equip($this->user, $twoHandedInv);

        // Shield should be unequipped
        $this->assertFalse($shieldInv->isEquipped());
        $this->assertNull($shieldInv->getEquippedSlot());

        // Two-handed weapon should be equipped
        $this->assertTrue($twoHandedInv->isEquipped());
        $this->assertSame(EquipmentSlot::Weapon, $twoHandedInv->getEquippedSlot());
    }

    public function testEquipOneHandedOverTwoHandedRemovesTwoHanded(): void
    {
        $oneHandedCatalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Weapon, false);
        $oneHandedInv = $this->createInventoryItem($oneHandedCatalog);

        // Existing two-handed weapon
        $twoHandedCatalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Weapon, true);
        $twoHandedInv = $this->createInventoryItem($twoHandedCatalog);
        $twoHandedInv->setEquipped(true);
        $twoHandedInv->setEquippedSlot(EquipmentSlot::Weapon);

        $this->repository->method('findEquippedBySlot')
            ->willReturnCallback(function (User $user, EquipmentSlot $slot) use ($twoHandedInv) {
                if ($slot === EquipmentSlot::Weapon) {
                    return [$twoHandedInv];
                }
                return [];
            });

        $this->repository->method('save');

        $this->service->equip($this->user, $oneHandedInv);

        // Two-handed should be unequipped
        $this->assertFalse($twoHandedInv->isEquipped());
        $this->assertNull($twoHandedInv->getEquippedSlot());

        // One-handed should be equipped
        $this->assertTrue($oneHandedInv->isEquipped());
        $this->assertSame(EquipmentSlot::Weapon, $oneHandedInv->getEquippedSlot());
    }

    public function testEquipShieldRemovesTwoHandedWeapon(): void
    {
        $shieldCatalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Shield);
        $shieldInv = $this->createInventoryItem($shieldCatalog);

        // Existing two-handed weapon
        $twoHandedCatalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Weapon, true);
        $twoHandedInv = $this->createInventoryItem($twoHandedCatalog);
        $twoHandedInv->setEquipped(true);
        $twoHandedInv->setEquippedSlot(EquipmentSlot::Weapon);

        $this->repository->method('findEquippedBySlot')
            ->willReturnCallback(function (User $user, EquipmentSlot $slot) use ($twoHandedInv) {
                if ($slot === EquipmentSlot::Weapon) {
                    return [$twoHandedInv];
                }
                if ($slot === EquipmentSlot::Shield) {
                    return [];
                }
                return [];
            });

        $this->repository->method('save');

        $this->service->equip($this->user, $shieldInv);

        // Two-handed weapon should be unequipped
        $this->assertFalse($twoHandedInv->isEquipped());
        $this->assertNull($twoHandedInv->getEquippedSlot());

        // Shield should be equipped
        $this->assertTrue($shieldInv->isEquipped());
        $this->assertSame(EquipmentSlot::Shield, $shieldInv->getEquippedSlot());
    }

    public function testEquipTwoRingsMaxAllowed(): void
    {
        // First ring already equipped
        $ring1Catalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Ring);
        $ring1Inv = $this->createInventoryItem($ring1Catalog);
        $ring1Inv->setEquipped(true);
        $ring1Inv->setEquippedSlot(EquipmentSlot::Ring);

        // Equipping second ring (should succeed, no unequip needed)
        $ring2Catalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Ring);
        $ring2Inv = $this->createInventoryItem($ring2Catalog);

        $this->repository->method('findEquippedBySlot')
            ->willReturn([$ring1Inv]);

        $this->repository->method('save');

        $this->service->equip($this->user, $ring2Inv);

        // Both should be equipped: ring1 stays, ring2 is new
        $this->assertTrue($ring1Inv->isEquipped());
        $this->assertTrue($ring2Inv->isEquipped());
        $this->assertSame(EquipmentSlot::Ring, $ring2Inv->getEquippedSlot());
    }

    public function testEquipThirdRingReplacesOldest(): void
    {
        // Two rings already equipped
        $ring1Catalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Ring);
        $ring1Inv = $this->createInventoryItem($ring1Catalog);
        $ring1Inv->setEquipped(true);
        $ring1Inv->setEquippedSlot(EquipmentSlot::Ring);

        $ring2Catalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Ring);
        $ring2Inv = $this->createInventoryItem($ring2Catalog);
        $ring2Inv->setEquipped(true);
        $ring2Inv->setEquippedSlot(EquipmentSlot::Ring);

        // Third ring
        $ring3Catalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Ring);
        $ring3Inv = $this->createInventoryItem($ring3Catalog);

        $this->repository->method('findEquippedBySlot')
            ->willReturn([$ring1Inv, $ring2Inv]);

        $this->repository->method('save');

        $this->service->equip($this->user, $ring3Inv);

        // Oldest ring (ring1) should be unequipped
        $this->assertFalse($ring1Inv->isEquipped());
        $this->assertNull($ring1Inv->getEquippedSlot());

        // Ring3 should be equipped
        $this->assertTrue($ring3Inv->isEquipped());
        $this->assertSame(EquipmentSlot::Ring, $ring3Inv->getEquippedSlot());
    }

    public function testEquipTwoBraceletsMaxAllowed(): void
    {
        // One bracelet equipped
        $brac1Catalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Bracelet);
        $brac1Inv = $this->createInventoryItem($brac1Catalog);
        $brac1Inv->setEquipped(true);
        $brac1Inv->setEquippedSlot(EquipmentSlot::Bracelet);

        // Second bracelet
        $brac2Catalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Bracelet);
        $brac2Inv = $this->createInventoryItem($brac2Catalog);

        $this->repository->method('findEquippedBySlot')
            ->willReturn([$brac1Inv]);

        $this->repository->method('save');

        $this->service->equip($this->user, $brac2Inv);

        $this->assertTrue($brac1Inv->isEquipped());
        $this->assertTrue($brac2Inv->isEquipped());
    }

    public function testStandardSlotReplacesExisting(): void
    {
        // Existing head item
        $helm1Catalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Head);
        $helm1Inv = $this->createInventoryItem($helm1Catalog);
        $helm1Inv->setEquipped(true);
        $helm1Inv->setEquippedSlot(EquipmentSlot::Head);

        // New head item
        $helm2Catalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Head);
        $helm2Inv = $this->createInventoryItem($helm2Catalog);

        $this->repository->method('findEquippedBySlot')
            ->willReturn([$helm1Inv]);

        $this->repository->method('save');

        $this->service->equip($this->user, $helm2Inv);

        // Old helmet should be unequipped
        $this->assertFalse($helm1Inv->isEquipped());
        $this->assertNull($helm1Inv->getEquippedSlot());

        // New helmet should be equipped
        $this->assertTrue($helm2Inv->isEquipped());
        $this->assertSame(EquipmentSlot::Head, $helm2Inv->getEquippedSlot());
    }

    public function testEquipNonEquipmentItemThrowsException(): void
    {
        $potionCatalog = $this->createCatalogItem(ItemType::Potion, null);
        $potionInv = $this->createInventoryItem($potionCatalog);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only equipment items can be equipped.');

        $this->service->equip($this->user, $potionInv);
    }

    public function testEquipItemWithNoSlotThrowsException(): void
    {
        $catalog = $this->createCatalogItem(ItemType::Equipment, null);
        $inv = $this->createInventoryItem($catalog);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This equipment item has no assigned slot.');

        $this->service->equip($this->user, $inv);
    }

    public function testUnequipItem(): void
    {
        $catalog = $this->createCatalogItem(ItemType::Equipment, EquipmentSlot::Head);
        $inv = $this->createInventoryItem($catalog);
        $inv->setEquipped(true);
        $inv->setEquippedSlot(EquipmentSlot::Head);

        $this->repository->expects($this->once())->method('save')->with($inv);

        $this->service->unequip($this->user, $inv);

        $this->assertFalse($inv->isEquipped());
        $this->assertNull($inv->getEquippedSlot());
    }

    public function testGetEquippedItems(): void
    {
        $items = [new UserInventory(), new UserInventory()];

        $this->repository->method('findEquippedByUser')
            ->with($this->user)
            ->willReturn($items);

        $result = $this->service->getEquippedItems($this->user);

        $this->assertSame($items, $result);
        $this->assertCount(2, $result);
    }
}
