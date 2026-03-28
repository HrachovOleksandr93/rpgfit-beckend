<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Inventory\Entity\ItemCatalog;
use App\Domain\Inventory\Entity\UserInventory;
use App\Domain\User\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class UserInventoryEntityTest extends TestCase
{
    public function testCreationGeneratesUuid(): void
    {
        $inventory = new UserInventory();

        $this->assertInstanceOf(Uuid::class, $inventory->getId());
    }

    public function testDefaultValues(): void
    {
        $inventory = new UserInventory();

        $this->assertSame(1, $inventory->getQuantity());
        $this->assertFalse($inventory->isEquipped());
        $this->assertInstanceOf(\DateTimeImmutable::class, $inventory->getObtainedAt());
        $this->assertNull($inventory->getExpiresAt());
        $this->assertNull($inventory->getDeletedAt());
        $this->assertNull($inventory->getCurrentDurability());
    }

    public function testSettersAndGetters(): void
    {
        $inventory = new UserInventory();
        $user = new User();
        $item = new ItemCatalog();
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $inventory->setUser($user);
        $inventory->setItemCatalog($item);
        $inventory->setQuantity(5);
        $inventory->setEquipped(true);
        $inventory->setCurrentDurability(80);
        $inventory->setExpiresAt($expiresAt);

        $this->assertSame($user, $inventory->getUser());
        $this->assertSame($item, $inventory->getItemCatalog());
        $this->assertSame(5, $inventory->getQuantity());
        $this->assertTrue($inventory->isEquipped());
        $this->assertSame(80, $inventory->getCurrentDurability());
        $this->assertSame($expiresAt, $inventory->getExpiresAt());
    }

    public function testSoftDelete(): void
    {
        $inventory = new UserInventory();

        $this->assertNull($inventory->getDeletedAt());

        $deletedAt = new \DateTimeImmutable();
        $inventory->setDeletedAt($deletedAt);

        $this->assertSame($deletedAt, $inventory->getDeletedAt());
    }

    public function testNullableFields(): void
    {
        $inventory = new UserInventory();

        $inventory->setCurrentDurability(null);
        $inventory->setExpiresAt(null);
        $inventory->setDeletedAt(null);

        $this->assertNull($inventory->getCurrentDurability());
        $this->assertNull($inventory->getExpiresAt());
        $this->assertNull($inventory->getDeletedAt());
    }

    public function testSetterChaining(): void
    {
        $inventory = new UserInventory();
        $user = new User();
        $item = new ItemCatalog();

        $result = $inventory->setUser($user)
            ->setItemCatalog($item)
            ->setQuantity(3)
            ->setEquipped(false)
            ->setCurrentDurability(50)
            ->setExpiresAt(null)
            ->setDeletedAt(null);

        $this->assertSame($inventory, $result);
    }
}
