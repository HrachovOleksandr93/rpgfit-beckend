<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Entity;

use App\Domain\Inventory\Enum\EquipmentSlot;
use App\Domain\User\Entity\User;
use App\Infrastructure\Inventory\Repository\UserInventoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Tracks item ownership: which items a user has, their quantity, and equipped status.
 *
 * Domain layer (Inventory bounded context). Each row represents one or more instances
 * of a catalog item owned by a user. Supports equipment (equipped flag, current durability)
 * and consumables (expiration timer). Uses soft delete (deletedAt) so item history is preserved.
 *
 * Data flow: User acquires item -> UserInventory row created -> user can equip/use item ->
 * stat bonuses from ItemCatalog.statBonuses applied to CharacterStats -> item consumed or
 * soft-deleted when destroyed/expired.
 *
 * Indexes optimize common queries: active items by user, equipped items, and non-deleted items.
 */
#[ORM\Entity(repositoryClass: UserInventoryRepository::class)]
#[ORM\Table(name: 'user_inventory')]
#[ORM\Index(name: 'idx_user_inventory_user_item', columns: ['user_id', 'item_catalog_id'])]
#[ORM\Index(name: 'idx_user_inventory_user_equipped', columns: ['user_id', 'equipped'])]
#[ORM\Index(name: 'idx_user_inventory_user_deleted', columns: ['user_id', 'deleted_at'])]
class UserInventory
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: ItemCatalog::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ItemCatalog $itemCatalog;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $quantity = 1;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $equipped = false;

    /** Which equipment slot this item occupies when equipped (null if not equipped) */
    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: EquipmentSlot::class)]
    private ?EquipmentSlot $equippedSlot = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $currentDurability = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $obtainedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->obtainedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getItemCatalog(): ItemCatalog
    {
        return $this->itemCatalog;
    }

    public function setItemCatalog(ItemCatalog $itemCatalog): self
    {
        $this->itemCatalog = $itemCatalog;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function isEquipped(): bool
    {
        return $this->equipped;
    }

    public function setEquipped(bool $equipped): self
    {
        $this->equipped = $equipped;

        return $this;
    }

    public function getEquippedSlot(): ?EquipmentSlot
    {
        return $this->equippedSlot;
    }

    public function setEquippedSlot(?EquipmentSlot $equippedSlot): self
    {
        $this->equippedSlot = $equippedSlot;

        return $this;
    }

    public function getCurrentDurability(): ?int
    {
        return $this->currentDurability;
    }

    public function setCurrentDurability(?int $currentDurability): self
    {
        $this->currentDurability = $currentDurability;

        return $this;
    }

    public function getObtainedAt(): \DateTimeImmutable
    {
        return $this->obtainedAt;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }
}
