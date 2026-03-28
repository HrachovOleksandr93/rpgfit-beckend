<?php

declare(strict_types=1);

namespace App\Domain\Skill\Entity;

use App\Infrastructure\Skill\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * RPG skill definition that can be unlocked by characters.
 *
 * Domain layer (Skill bounded context). Each skill provides passive stat bonuses
 * (via SkillStatBonus one-to-many relationship) and has a minimum level requirement.
 * Skills are unlocked by users through scroll items or level-up rewards, tracked
 * via the UserSkill join entity.
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

    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $requiredLevel = 1;

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

    public function getRequiredLevel(): int
    {
        return $this->requiredLevel;
    }

    public function setRequiredLevel(int $requiredLevel): self
    {
        $this->requiredLevel = $requiredLevel;

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
