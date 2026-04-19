<?php

declare(strict_types=1);

namespace App\Domain\Skill\Entity;

use App\Domain\Media\Entity\MediaFile;
use App\Infrastructure\Skill\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * RPG skill definition that can be unlocked by characters.
 *
 * Domain layer (Skill bounded context). Each skill provides stat bonuses
 * (via SkillStatBonus one-to-many relationship) and has a minimum level requirement.
 * Skills can be passive (always active) or active (with duration and cooldown).
 * They may be restricted to a specific race, linked to professions, or universal.
 *
 * Managed exclusively via the Sonata admin panel. Game designers configure skills
 * and their stat bonuses to balance the RPG progression system.
 *
 * Hierarchy: Skill -> SkillStatBonus (stat bonuses), User -> UserSkill -> Skill (unlocked skills)
 */
#[ORM\Entity(repositoryClass: SkillRepository::class)]
#[ORM\Table(name: 'skills')]
class Skill
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

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $icon = null;

    /** Optional image for this skill, linked via MediaFile entity */
    #[ORM\ManyToOne(targetEntity: MediaFile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?MediaFile $image = null;

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $requiredLevel = 1;

    /** Skill type: 'passive' (always active) or 'active' (has duration/cooldown) */
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'passive'])]
    private string $skillType = 'passive';

    /** Duration in minutes for active skills (null for passive) */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null;

    /** Cooldown in minutes for active skills (null for passive) */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $cooldown = null;

    /**
     * Legacy race restriction column (human/orc/dwarf/dark_elf/light_elf).
     * Races were removed per founder decision D4 (2026-04-18); the column
     * is kept for backward compatibility but is no longer populated.
     */
    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $raceRestriction = null;

    /** Skill tier: 1, 2, or 3 for profession skills; null for race/universal skills */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $tier = null;

    /** Whether this skill is available to all players regardless of race or profession */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isUniversal = false;

    /** Whether this is a race-specific passive skill */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isRaceSkill = false;

    /** @var Collection<int, SkillStatBonus> */
    #[ORM\OneToMany(targetEntity: SkillStatBonus::class, mappedBy: 'skill', cascade: ['persist', 'remove'])]
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

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

    public function getRequiredLevel(): int
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(int $requiredLevel): self
    {
        $this->requiredLevel = $requiredLevel;

        return $this;
    }

    public function getSkillType(): string
    {
        return $this->skillType;
    }

    public function setSkillType(string $skillType): self
    {
        $this->skillType = $skillType;

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

    public function getCooldown(): ?int
    {
        return $this->cooldown;
    }

    public function setCooldown(?int $cooldown): self
    {
        $this->cooldown = $cooldown;

        return $this;
    }

    public function getRaceRestriction(): ?string
    {
        return $this->raceRestriction;
    }

    public function setRaceRestriction(?string $raceRestriction): self
    {
        $this->raceRestriction = $raceRestriction;

        return $this;
    }

    public function getTier(): ?int
    {
        return $this->tier;
    }

    public function setTier(?int $tier): self
    {
        $this->tier = $tier;

        return $this;
    }

    public function getIsUniversal(): bool
    {
        return $this->isUniversal;
    }

    public function setIsUniversal(bool $isUniversal): self
    {
        $this->isUniversal = $isUniversal;

        return $this;
    }

    public function getIsRaceSkill(): bool
    {
        return $this->isRaceSkill;
    }

    public function setIsRaceSkill(bool $isRaceSkill): self
    {
        $this->isRaceSkill = $isRaceSkill;

        return $this;
    }

    /** @return Collection<int, SkillStatBonus> */
    public function getStatBonuses(): Collection
    {
        return $this->statBonuses;
    }

    public function addStatBonus(SkillStatBonus $statBonus): self
    {
        if (!$this->statBonuses->contains($statBonus)) {
            $this->statBonuses->add($statBonus);
            $statBonus->setSkill($this);
        }

        return $this;
    }

    public function removeStatBonus(SkillStatBonus $statBonus): self
    {
        $this->statBonuses->removeElement($statBonus);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
