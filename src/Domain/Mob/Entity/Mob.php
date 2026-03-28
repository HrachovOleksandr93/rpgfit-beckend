<?php

declare(strict_types=1);

namespace App\Domain\Mob\Entity;

use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Media\Entity\MediaFile;
use App\Infrastructure\Mob\Repository\MobRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A hostile creature (mob) that players can encounter and defeat in the game world.
 *
 * Domain layer (Mob bounded context). Each mob has a level, hit points, and XP reward.
 * The rarity tier reuses ItemRarity to indicate how rare/powerful a mob is.
 * Mob definitions are imported via CSV and managed through the Sonata admin panel.
 *
 * Hierarchy: Mob (standalone entity, linked optionally to MediaFile for images)
 */
#[ORM\Entity(repositoryClass: MobRepository::class)]
#[ORM\Table(name: 'mobs')]
#[ORM\Index(name: 'idx_mob_level', columns: ['level'])]
class Mob
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** Display name shown to players */
    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    /** URL-safe unique identifier for lookups */
    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $slug;

    /** Mob level from 1 to 100, determines difficulty and scaling */
    #[ORM\Column(type: 'integer')]
    private int $level;

    /** Base hit points the mob has before any modifiers */
    #[ORM\Column(type: 'integer')]
    private int $hp;

    /** Base experience points awarded to the player for defeating this mob */
    #[ORM\Column(type: 'integer')]
    private int $xpReward;

    /** Optional flavor text describing the mob */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** Optional image for this mob, linked via MediaFile entity */
    #[ORM\ManyToOne(targetEntity: MediaFile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?MediaFile $image = null;

    /** Rarity tier indicating how rare/powerful this mob is */
    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: ItemRarity::class)]
    private ?ItemRarity $rarity = null;

    /** Timestamp when this mob was first created */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
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

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getHp(): int
    {
        return $this->hp;
    }

    public function setHp(int $hp): self
    {
        $this->hp = $hp;

        return $this;
    }

    public function getXpReward(): int
    {
        return $this->xpReward;
    }

    public function setXpReward(int $xpReward): self
    {
        $this->xpReward = $xpReward;

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

    public function getImage(): ?MediaFile
    {
        return $this->image;
    }

    public function setImage(?MediaFile $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getRarity(): ?ItemRarity
    {
        return $this->rarity;
    }

    public function setRarity(?ItemRarity $rarity): self
    {
        $this->rarity = $rarity;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
