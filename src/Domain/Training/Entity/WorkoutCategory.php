<?php

declare(strict_types=1);

namespace App\Domain\Training\Entity;

use App\Infrastructure\Training\Repository\WorkoutCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Admin-managed category that groups related exercise types.
 *
 * Domain layer (Training bounded context). Examples: "Cardio", "Strength", "Flexibility".
 * Each category contains multiple ExerciseType entries (one-to-many).
 *
 * This is reference/configuration data managed exclusively via the Sonata admin panel.
 * The slug field provides a URL-safe identifier for API lookups.
 *
 * Hierarchy: WorkoutCategory -> ExerciseType -> ExerciseStatReward
 */
#[ORM\Entity(repositoryClass: WorkoutCategoryRepository::class)]
#[ORM\Table(name: 'workout_categories')]
class WorkoutCategory
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

    /** @var Collection<int, ExerciseType> */
    #[ORM\OneToMany(targetEntity: ExerciseType::class, mappedBy: 'category', cascade: ['persist', 'remove'])]
    private Collection $exerciseTypes;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->exerciseTypes = new ArrayCollection();
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

    /** @return Collection<int, ExerciseType> */
    public function getExerciseTypes(): Collection
    {
        return $this->exerciseTypes;
    }

    public function addExerciseType(ExerciseType $exerciseType): self
    {
        if (!$this->exerciseTypes->contains($exerciseType)) {
            $this->exerciseTypes->add($exerciseType);
            $exerciseType->setCategory($this);
        }

        return $this;
    }

    public function removeExerciseType(ExerciseType $exerciseType): self
    {
        $this->exerciseTypes->removeElement($exerciseType);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
