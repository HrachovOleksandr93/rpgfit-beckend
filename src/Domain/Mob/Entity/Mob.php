<?php

declare(strict_types=1);

namespace App\Domain\Mob\Entity;

use App\Domain\Inventory\Enum\ItemRarity;
use App\Domain\Media\Entity\MediaFile;
use App\Domain\Mob\Enum\MobArchetype;
use App\Domain\Mob\Enum\MobBehavior;
use App\Domain\Mob\Enum\MobClassTier;
use App\Domain\Shared\Enum\Realm;
use App\Infrastructure\Mob\Repository\MobRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A hostile creature (mob) that players can encounter and defeat in the game world.
 *
 * Domain layer (Mob bounded context). Each mob has a level, hit points, and XP reward.
 * The rarity tier reuses ItemRarity to indicate how rare/powerful a mob is.
 * Extended 2026-04-18 with 8 fields (realm, class_tier, behavior, archetype,
 * visual_keywords, is_champion, champion_decoration, accepts_champion) per
 * BA/outputs/09-mob-bestiary.md §1.
 *
 * Mob definitions are seeded from YAML per realm (`data/mobs/{realm}.yaml`)
 * via `app:seed-mobs` and managed through the Sonata admin panel. Legacy CSV
 * import (`app:import-mobs`) is retained for one release as a fallback.
 *
 * Hierarchy: Mob (standalone entity, linked optionally to MediaFile for images)
 */
#[ORM\Entity(repositoryClass: MobRepository::class)]
#[ORM\Table(name: 'mobs')]
#[ORM\Index(name: 'idx_mob_level', columns: ['level'])]
#[ORM\Index(name: 'idx_mob_realm_tier', columns: ['realm', 'class_tier'])]
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

    /** Realm this mob belongs to — used for artifact bonus matching and spawn filtering. */
    #[ORM\Column(type: 'string', length: 20, enumType: Realm::class, options: ['default' => 'neutral'])]
    private Realm $realm = Realm::Neutral;

    /** Mob class tier (I..IV). Used for raid gating and loot tables. */
    #[ORM\Column(type: 'string', length: 4, enumType: MobClassTier::class, options: ['default' => 'I'])]
    private MobClassTier $classTier = MobClassTier::I;

    /** Behavior dictating how the mob can be defeated (physical, ritual, oracle_task, team). */
    #[ORM\Column(type: 'string', length: 20, enumType: MobBehavior::class, options: ['default' => 'physical'])]
    private MobBehavior $behavior = MobBehavior::Physical;

    /** Body-plan archetype used for visual direction and champion eligibility. */
    #[ORM\Column(type: 'string', length: 20, enumType: MobArchetype::class, options: ['default' => 'beast'])]
    private MobArchetype $archetype = MobArchetype::Beast;

    /**
     * Artist-facing keywords that describe the mob's silhouette. Stored as JSON array.
     *
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    private array $visualKeywords = [];

    /** Whether this record represents a champion variant (10-15% spawn roll). */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isChampion = false;

    /** Decoration carried by the champion (e.g. apple_watch, powerbank_necklace). Null if not champion. */
    #[ORM\Column(type: 'string', length: 40, nullable: true)]
    private ?string $championDecoration = null;

    /**
     * Whether this mob is eligible for the champion spawn pool.
     * False for incorporeal / swarm / tier-IV divine mobs.
     */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $acceptsChampion = true;

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

    public function getRealm(): Realm
    {
        return $this->realm;
    }

    public function setRealm(Realm $realm): self
    {
        $this->realm = $realm;

        return $this;
    }

    public function getClassTier(): MobClassTier
    {
        return $this->classTier;
    }

    public function setClassTier(MobClassTier $classTier): self
    {
        $this->classTier = $classTier;

        return $this;
    }

    public function getBehavior(): MobBehavior
    {
        return $this->behavior;
    }

    public function setBehavior(MobBehavior $behavior): self
    {
        $this->behavior = $behavior;

        return $this;
    }

    public function getArchetype(): MobArchetype
    {
        return $this->archetype;
    }

    public function setArchetype(MobArchetype $archetype): self
    {
        $this->archetype = $archetype;

        return $this;
    }

    /** @return string[] */
    public function getVisualKeywords(): array
    {
        return $this->visualKeywords;
    }

    /** @param string[] $visualKeywords */
    public function setVisualKeywords(array $visualKeywords): self
    {
        $this->visualKeywords = array_values(array_map('strval', $visualKeywords));

        return $this;
    }

    public function isChampion(): bool
    {
        return $this->isChampion;
    }

    public function setIsChampion(bool $isChampion): self
    {
        $this->isChampion = $isChampion;

        return $this;
    }

    public function getChampionDecoration(): ?string
    {
        return $this->championDecoration;
    }

    public function setChampionDecoration(?string $championDecoration): self
    {
        $this->championDecoration = $championDecoration;

        return $this;
    }

    public function acceptsChampion(): bool
    {
        return $this->acceptsChampion;
    }

    public function setAcceptsChampion(bool $acceptsChampion): self
    {
        $this->acceptsChampion = $acceptsChampion;

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
