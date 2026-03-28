<?php

declare(strict_types=1);

namespace App\Domain\Workout\Entity;

use App\Infrastructure\Workout\Repository\WorkoutPlanExerciseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A single exercise within a workout plan, with prescribed sets/reps/rest.
 *
 * Domain layer (Workout bounded context). Links a WorkoutPlan to an Exercise
 * with specific programming parameters (sets, reps range, rest time) and
 * an ordering index that determines exercise sequence in the session.
 *
 * Each plan exercise can have multiple WorkoutPlanExerciseLog entries
 * recording the actual performance of each set.
 */
#[ORM\Entity(repositoryClass: WorkoutPlanExerciseRepository::class)]
#[ORM\Table(name: 'workout_plan_exercises')]
#[ORM\Index(name: 'idx_plan_exercise_order', columns: ['workout_plan_id', 'order_index'])]
class WorkoutPlanExercise
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: WorkoutPlan::class, inversedBy: 'exercises')]
    #[ORM\JoinColumn(nullable: false)]
    private WorkoutPlan $workoutPlan;

    #[ORM\ManyToOne(targetEntity: Exercise::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Exercise $exercise;

    /** Position of this exercise in the workout (1, 2, 3...) */
    #[ORM\Column(type: 'integer')]
    private int $orderIndex;

    #[ORM\Column(type: 'integer')]
    private int $sets;

    #[ORM\Column(type: 'integer')]
    private int $repsMin;

    #[ORM\Column(type: 'integer')]
    private int $repsMax;

    /** Rest period between sets in seconds */
    #[ORM\Column(type: 'integer')]
    private int $restSeconds;

    /** Optional coaching note, e.g. "Focus on slow negative" */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $notes = null;

    /** @var Collection<int, WorkoutPlanExerciseLog> */
    #[ORM\OneToMany(targetEntity: WorkoutPlanExerciseLog::class, mappedBy: 'planExercise', cascade: ['persist', 'remove'])]
    private Collection $logs;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->logs = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getWorkoutPlan(): WorkoutPlan
    {
        return $this->workoutPlan;
    }

    public function setWorkoutPlan(WorkoutPlan $workoutPlan): self
    {
        $this->workoutPlan = $workoutPlan;

        return $this;
    }

    public function getExercise(): Exercise
    {
        return $this->exercise;
    }

    public function setExercise(Exercise $exercise): self
    {
        $this->exercise = $exercise;

        return $this;
    }

    public function getOrderIndex(): int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(int $orderIndex): self
    {
        $this->orderIndex = $orderIndex;

        return $this;
    }

    public function getSets(): int
    {
        return $this->sets;
    }

    public function setSets(int $sets): self
    {
        $this->sets = $sets;

        return $this;
    }

    public function getRepsMin(): int
    {
        return $this->repsMin;
    }

    public function setRepsMin(int $repsMin): self
    {
        $this->repsMin = $repsMin;

        return $this;
    }

    public function getRepsMax(): int
    {
        return $this->repsMax;
    }

    public function setRepsMax(int $repsMax): self
    {
        $this->repsMax = $repsMax;

        return $this;
    }

    public function getRestSeconds(): int
    {
        return $this->restSeconds;
    }

    public function setRestSeconds(int $restSeconds): self
    {
        $this->restSeconds = $restSeconds;

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

    /** @return Collection<int, WorkoutPlanExerciseLog> */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(WorkoutPlanExerciseLog $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setPlanExercise($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('%s (Order: %d)', $this->exercise->getName(), $this->orderIndex);
    }
}
