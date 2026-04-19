<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Entity;

use App\Domain\Inventory\Enum\EquipmentSlot;
use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Inventory\Enum\ItemType;
use App\Domain\Media\Entity\MediaFile;
use App\Domain\Shared\Enum\Realm;
use App\Infrastructure\Inventory\Repository\ItemCatalogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Master definition of an item available in the game world.
 *
 * Domain layer (Inventory bounded context). This is a catalog/template entity — it defines
 * what an item IS (name, type, rarity, stats), not ownership. Player ownership is tracked
 * via UserInventory which references this catalog entry.
 *
 * Supports three item types: equipment (wearable gear with durability and a slot),
 * scrolls (used to unlock skills), and potions (consumables with timed effects).
 * Each item can provide stat bonuses via the ItemStatBonus one-to-many relationship.
 *
 * Managed exclusively via the Sonata admin panel. Game designers configure items
 * and their stat bonuses to balance the RPG progression system.
 *
 * Hierarchy: ItemCatalog -> ItemStatBonus (stat bonuses), User -> UserInventory -> ItemCatalog (owned items)
 */
#[ORM\Entity(repositoryClass: ItemCatalogRepository::class)]
#[ORM\Table(name: 'item_catalog')]
class ItemCatalog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20, enumType: ItemType::class)]
    private ItemType $itemType;

    #[ORM\Column(type: 'string', length: 20, enumType: ItemRarity::class)]
    private ItemRarity $rarity;

    /**
     * Realm this artifact is bound to, if any. When `realm === mob.realm`
     * the BattleResultCalculator applies +40% damage (BUSINESS_LOGIC §12).
     * Null means unbound / generic gear.
     */
    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: Realm::class)]
    private ?Realm $realm = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: EquipmentSlot::class)]
    private ?EquipmentSlot $slot = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $durability = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null;

    /** Whether this weapon requires both hands (only relevant for weapon-type equipment) */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $twoHanded = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $stackable = false;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $maxStack = 1;

    /** Optional image for this catalog item, linked via MediaFile entity */
    #[ORM\ManyToOne(targetEntity: MediaFile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?MediaFile $image = null;

    /** @var Collection<int, ItemStatBonus> */
    #[ORM\OneToMany(targetEntity: ItemStatBonus::class, mappedBy: 'itemCatalog', cascade: ['persist', 'remove'])]
    private Collection $statBonuses;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->statBonuses = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getItemType(): ItemType
    {
        return $this->itemType;
    }

    public function setItemType(ItemType $itemType): self
    {
        $this->itemType = $itemType;

        return $this;
    }

    public function getRarity(): ItemRarity
    {
        return $this->rarity;
    }

    public function setRarity(ItemRarity $rarity): self
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function getRealm(): ?Realm
    {
        return $this->realm;
    }

    public function setRealm(?Realm $realm): self
    {
        $this->realm = $realm;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getSlot(): ?EquipmentSlot
    {
        return $this->slot;
    }

    public function setSlot(?EquipmentSlot $slot): self
    {
        $this->slot = $slot;

        return $this;
    }

    public function getDurability(): ?int
    {
        return $this->durability;
    }

    public function setDurability(?int $durability): self
    {
        $this->durability = $durability;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function isStackable(): bool
    {
        return $this->stackable;
    }

    public function setStackable(bool $stackable): self
    {
        $this->stackable = $stackable;

        return $this;
    }

    public function getMaxStack(): int
    {
        return $this->maxStack;
    }

    public function setMaxStack(int $maxStack): self
    {
        $this->maxStack = $maxStack;

        return $this;
    }

    public function isTwoHanded(): bool
    {
        return $this->twoHanded;
    }

    public function setTwoHanded(bool $twoHanded): self
    {
        $this->twoHanded = $twoHanded;

        return $this;
    }

    public function getImage(): ?MediaFile
    {
        return $this->image;
    }

    public function setImage(?MediaFile $image): self
    {
        $this->image = $image;

        return $this;
    }

    /** @return Collection<int, ItemStatBonus> */
    public function getStatBonuses(): Collection
    {
        return $this->statBonuses;
    }

    public function addStatBonus(ItemStatBonus $statBonus): self
    {
        if (!$this->statBonuses->contains($statBonus)) {
            $this->statBonuses->add($statBonus);
            $statBonus->setItemCatalog($this);
        }

        return $this;
    }

    public function removeStatBonus(ItemStatBonus $statBonus): self
    {
        $this->statBonuses->removeElement($statBonus);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
