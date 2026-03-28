<?php

declare(strict_types=1);

namespace App\Domain\Workout\Entity;

use App\Infrastructure\Workout\Repository\WorkoutPlanExerciseLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Records actual performance of a single set within a planned exercise.
 *
 * Domain layer (Workout bounded context). Each log entry captures the actual
 * reps, weight, and/or duration performed for one set of a WorkoutPlanExercise.
 *
 * Example: if the plan prescribes 3 sets of bench press, there will be 3 log
 * entries with setNumber 1, 2, 3 recording what the user actually achieved.
 */
#[ORM\Entity(repositoryClass: WorkoutPlanExerciseLogRepository::class)]
#[ORM\Table(name: 'workout_plan_exercise_logs')]
class WorkoutPlanExerciseLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: WorkoutPlanExercise::class, inversedBy: 'logs')]
    #[ORM\JoinColumn(nullable: false)]
    private WorkoutPlanExercise $planExercise;

    /** Which set this log represents (1, 2, 3...) */
    #[ORM\Column(type: 'integer')]
    private int $setNumber;

    /** Actual reps performed, null for timed exercises */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $reps = null;

    /** Weight used in kg, null for bodyweight exercises */
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $weight = null;

    /** Duration in seconds for timed exercises (e.g. plank) */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $completedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->completedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPlanExercise(): WorkoutPlanExercise
    {
        return $this->planExercise;
    }

    public function setPlanExercise(WorkoutPlanExercise $planExercise): self
    {
        $this->planExercise = $planExercise;

        return $this;
    }

    public function getSetNumber(): int
    {
        return $this->setNumber;
    }

    public function setSetNumber(int $setNumber): self
    {
        $this->setNumber = $setNumber;

        return $this;
    }

    public function getReps(): ?int
    {
        return $this->reps;
    }

    public function setReps(?int $reps): self
    {
        $this->reps = $reps;

        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): self
    {
        $this->weight = $weight;

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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getCompletedAt(): \DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }
}
