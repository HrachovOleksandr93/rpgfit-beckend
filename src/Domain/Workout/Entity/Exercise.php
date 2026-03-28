<?php

declare(strict_types=1);

namespace App\Domain\Workout\Entity;

use App\Domain\Media\Entity\MediaFile;
use App\Domain\Workout\Enum\Equipment;
use App\Domain\Workout\Enum\ExerciseDifficulty;
use App\Domain\Workout\Enum\ExerciseMovementType;
use App\Domain\Workout\Enum\MuscleGroup;
use App\Infrastructure\Workout\Repository\ExerciseRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A specific gym exercise with targeting, equipment, and default programming.
 *
 * Domain layer (Workout bounded context). Each exercise targets a primary muscle
 * group and optionally secondary muscles. Exercises are seeded from the research
 * database and used by the workout plan generator to build training sessions.
 *
 * The priority field (1-5) controls exercise ordering in generated plans:
 * 1 = heavy compound movements first, 5 = light isolation last.
 *
 * isBaseExercise flags fundamental movements like bench press, squat, and deadlift.
 */
#[ORM\Entity(repositoryClass: ExerciseRepository::class)]
#[ORM\Table(name: 'exercises')]
#[ORM\Index(name: 'idx_exercise_primary_muscle', columns: ['primary_muscle'])]
#[ORM\Index(name: 'idx_exercise_difficulty', columns: ['difficulty'])]
#[ORM\Index(name: 'idx_exercise_priority', columns: ['priority'])]
class Exercise
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'string', length: 150, unique: true)]
    private string $slug;

    /** Primary muscle group this exercise targets */
    #[ORM\Column(type: 'string', length: 20, enumType: MuscleGroup::class)]
    private MuscleGroup $primaryMuscle;

    /** Secondary muscle groups involved, stored as JSON array of MuscleGroup values */
    #[ORM\Column(type: 'json')]
    private array $secondaryMuscles = [];

    #[ORM\Column(type: 'string', length: 20, enumType: Equipment::class)]
    private Equipment $equipment;

    #[ORM\Column(type: 'string', length: 20, enumType: ExerciseDifficulty::class)]
    private ExerciseDifficulty $difficulty;

    #[ORM\Column(type: 'string', length: 20, enumType: ExerciseMovementType::class)]
    private ExerciseMovementType $movementType;

    /** Ordering priority: 1 = heavy compound first, 5 = light isolation last */
    #[ORM\Column(type: 'integer')]
    private int $priority;

    /** Whether this is a fundamental movement (bench, squat, deadlift, etc.) */
    #[ORM\Column(type: 'boolean')]
    private bool $isBaseExercise = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** Optional illustration or demonstration image */
    #[ORM\ManyToOne(targetEntity: MediaFile::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?MediaFile $image = null;

    #[ORM\Column(type: 'integer')]
    private int $defaultSets = 3;

    #[ORM\Column(type: 'integer')]
    private int $defaultRepsMin = 8;

    #[ORM\Column(type: 'integer')]
    private int $defaultRepsMax = 12;

    /** Default rest period between sets in seconds */
    #[ORM\Column(type: 'integer')]
    private int $defaultRestSeconds = 90;

    public function __construct()
    {
        $this->id = Uuid::v4();
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

    public function getPrimaryMuscle(): MuscleGroup
    {
        return $this->primaryMuscle;
    }

    public function setPrimaryMuscle(MuscleGroup $primaryMuscle): self
    {
        $this->primaryMuscle = $primaryMuscle;

        return $this;
    }

    /** @return string[] */
    public function getSecondaryMuscles(): array
    {
        return $this->secondaryMuscles;
    }

    /** @param string[] $secondaryMuscles */
    public function setSecondaryMuscles(array $secondaryMuscles): self
    {
        $this->secondaryMuscles = $secondaryMuscles;

        return $this;
    }

    public function getEquipment(): Equipment
    {
        return $this->equipment;
    }

    public function setEquipment(Equipment $equipment): self
    {
        $this->equipment = $equipment;

        return $this;
    }

    public function getDifficulty(): ExerciseDifficulty
    {
        return $this->difficulty;
    }

    public function setDifficulty(ExerciseDifficulty $difficulty): self
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getMovementType(): ExerciseMovementType
    {
        return $this->movementType;
    }

    public function setMovementType(ExerciseMovementType $movementType): self
    {
        $this->movementType = $movementType;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function isBaseExercise(): bool
    {
        return $this->isBaseExercise;
    }

    public function setIsBaseExercise(bool $isBaseExercise): self
    {
        $this->isBaseExercise = $isBaseExercise;

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

    public function getDefaultSets(): int
    {
        return $this->defaultSets;
    }

    public function setDefaultSets(int $defaultSets): self
    {
        $this->defaultSets = $defaultSets;

        return $this;
    }

    public function getDefaultRepsMin(): int
    {
        return $this->defaultRepsMin;
    }

    public function setDefaultRepsMin(int $defaultRepsMin): self
    {
        $this->defaultRepsMin = $defaultRepsMin;

        return $this;
    }

    public function getDefaultRepsMax(): int
    {
        return $this->defaultRepsMax;
    }

    public function setDefaultRepsMax(int $defaultRepsMax): self
    {
        $this->defaultRepsMax = $defaultRepsMax;

        return $this;
    }

    public function getDefaultRestSeconds(): int
    {
        return $this->defaultRestSeconds;
    }

    public function setDefaultRestSeconds(int $defaultRestSeconds): self
    {
        $this->defaultRestSeconds = $defaultRestSeconds;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
