<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Entity;

use App\Domain\Character\Enum\StatType;
use App\Infrastructure\Inventory\Repository\ItemStatBonusRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Defines how many RPG stat points a specific catalog item provides as a bonus.
 *
 * Domain layer (Inventory bounded context). Each row maps an ItemCatalog entry to a
 * StatType (STR/DEX/CON) with a point value. One item can provide bonuses to multiple stats.
 * For example: "Iron Sword" -> STR +5, DEX +2.
 *
 * When a user equips an item (via UserInventory), the system applies these stat bonuses
 * to the character's stats. Follows the same pattern as ExerciseStatReward and SkillStatBonus.
 *
 * Managed exclusively via the Sonata admin panel.
 */
#[ORM\Entity(repositoryClass: ItemStatBonusRepository::class)]
#[ORM\Table(name: 'item_stat_bonuses')]
#[ORM\Index(name: 'idx_item_stat_bonus_item', columns: ['item_catalog_id'])]
class ItemStatBonus
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: ItemCatalog::class, inversedBy: 'statBonuses')]
    #[ORM\JoinColumn(nullable: false)]
    private ItemCatalog $itemCatalog;

    #[ORM\Column(type: 'string', length: 10, enumType: StatType::class)]
    private StatType $statType;

    #[ORM\Column(type: 'integer')]
    private int $points;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getId(): Uuid
    {
        return $this->id;
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

    public function getStatType(): StatType
    {
        return $this->statType;
    }

    public function setStatType(StatType $statType): self
    {
        $this->statType = $statType;

        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;

        return $this;
    }
}
