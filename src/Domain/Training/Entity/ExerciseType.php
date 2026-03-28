<?php

declare(strict_types=1);

namespace App\Domain\Training\Entity;

use App\Infrastructure\Training\Repository\ExerciseTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A specific exercise within a workout category, with configurable stat rewards.
 *
 * Domain layer (Training bounded context). Examples: "Bench Press" (in Strength category),
 * "Running" (in Cardio category).
 *
 * Each ExerciseType belongs to one WorkoutCategory and can have multiple ExerciseStatReward
 * entries that define how many STR/DEX/CON points the user earns for performing this exercise.
 *
 * This is reference/configuration data managed exclusively via the Sonata admin panel.
 * The slug field provides a URL-safe identifier for API lookups.
 *
 * Hierarchy: WorkoutCategory -> ExerciseType -> ExerciseStatReward
 */
#[ORM\Entity(repositoryClass: ExerciseTypeRepository::class)]
#[ORM\Table(name: 'exercise_types')]
class ExerciseType
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: WorkoutCategory::class, inversedBy: 'exerciseTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private WorkoutCategory $category;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, ExerciseStatReward> */
    #[ORM\OneToMany(targetEntity: ExerciseStatReward::class, mappedBy: 'exerciseType', cascade: ['persist', 'remove'])]
    private Collection $statRewards;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->statRewards = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCategory(): WorkoutCategory
    {
        return $this->category;
    }

    public function setCategory(WorkoutCategory $category): self
    {
        $this->category = $category;

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

    /** @return Collection<int, ExerciseStatReward> */
    public function getStatRewards(): Collection
    {
        return $this->statRewards;
    }

    public function addStatReward(ExerciseStatReward $statReward): self
    {
        if (!$this->statRewards->contains($statReward)) {
            $this->statRewards->add($statReward);
            $statReward->setExerciseType($this);
        }

        return $this;
    }

    public function removeStatReward(ExerciseStatReward $statReward): self
    {
        $this->statRewards->removeElement($statReward);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
