<?php

declare(strict_types=1);

namespace App\Domain\Activity\Entity;

use App\Infrastructure\Activity\Repository\ActivityCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Represents one of the 16 RPG activity categories (e.g. Combat, Running, Swimming).
 *
 * Domain layer (Activity bounded context). Each category groups related activity types
 * and contains three profession tiers that users can unlock through training.
 */
#[ORM\Entity(repositoryClass: ActivityCategoryRepository::class)]
#[ORM\Table(name: 'activity_categories')]
class ActivityCategory
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** Unique slug identifier, e.g. 'combat', 'running' */
    #[ORM\Column(type: 'string', length: 50, unique: true)]
    private string $slug;

    /** Human-readable display name */
    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    /** RPG-flavored description of the category */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** Professions belonging to this category (3 tiers) */
    /** @var Collection<int, Profession> */
    #[ORM\OneToMany(targetEntity: Profession::class, mappedBy: 'category', cascade: ['persist'])]
    private Collection $professions;

    /** Activity types belonging to this category */
    /** @var Collection<int, ActivityType> */
    #[ORM\OneToMany(targetEntity: ActivityType::class, mappedBy: 'category', cascade: ['persist'])]
    private Collection $activityTypes;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->professions = new ArrayCollection();
        $this->activityTypes = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    /** @return Collection<int, Profession> */
    public function getProfessions(): Collection
    {
        return $this->professions;
    }

    public function addProfession(Profession $profession): self
    {
        if (!$this->professions->contains($profession)) {
            $this->professions->add($profession);
            $profession->setCategory($this);
        }

        return $this;
    }

    /** @return Collection<int, ActivityType> */
    public function getActivityTypes(): Collection
    {
        return $this->activityTypes;
    }

    public function addActivityType(ActivityType $activityType): self
    {
        if (!$this->activityTypes->contains($activityType)) {
            $this->activityTypes->add($activityType);
            $activityType->setCategory($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
