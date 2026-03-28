<?php

declare(strict_types=1);

namespace App\Domain\Workout\Entity;

use App\Domain\User\Entity\User;
use App\Domain\Workout\Enum\WorkoutPlanStatus;
use App\Infrastructure\Workout\Repository\WorkoutPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A scheduled workout session assigned to a user with exercises and targets.
 *
 * Domain layer (Workout bounded context). Represents a single planned training
 * session (e.g. "Chest & Triceps Day") with a status lifecycle:
 * pending -> in_progress -> completed/skipped.
 *
 * Supports both strength training (exercises + muscle groups) and cardio
 * (distance, duration, calories targets with tiered XP rewards).
 *
 * Indexed by (user_id, planned_at) and (user_id, status) for efficient queries.
 */
#[ORM\Entity(repositoryClass: WorkoutPlanRepository::class)]
#[ORM\Table(name: 'workout_plans')]
#[ORM\Index(name: 'idx_plan_user_planned', columns: ['user_id', 'planned_at'])]
#[ORM\Index(name: 'idx_plan_user_status', columns: ['user_id', 'status'])]
class WorkoutPlan
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /** Display name for the workout session, e.g. "Chest & Triceps Day" */
    #[ORM\Column(type: 'string', length: 150)]
    private string $name;

    #[ORM\Column(type: 'string', length: 20, enumType: WorkoutPlanStatus::class)]
    private WorkoutPlanStatus $status;

    /** Activity type slug, e.g. "running", "strength_training", "yoga" */
    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $activityType = null;

    /** Target muscle groups for this session, stored as JSON array */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $targetMuscleGroups = null;

    /** When this workout is scheduled to be performed */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $plannedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    /** Target distance for cardio workouts, in meters */
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $targetDistance = null;

    /** Target duration in minutes */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $targetDuration = null;

    /** Target calories to burn */
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $targetCalories = null;

    /**
     * Tiered XP reward thresholds, e.g.:
     * {"bronze": {"threshold": 3000, "xp": 50}, "silver": {"threshold": 5000, "xp": 100}}
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $rewardTiers = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, WorkoutPlanExercise> */
    #[ORM\OneToMany(targetEntity: WorkoutPlanExercise::class, mappedBy: 'workoutPlan', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['orderIndex' => 'ASC'])]
    private Collection $exercises;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->status = WorkoutPlanStatus::Pending;
        $this->createdAt = new \DateTimeImmutable();
        $this->exercises = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

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

    public function getStatus(): WorkoutPlanStatus
    {
        return $this->status;
    }

    public function setStatus(WorkoutPlanStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getActivityType(): ?string
    {
        return $this->activityType;
    }

    public function setActivityType(?string $activityType): self
    {
        $this->activityType = $activityType;

        return $this;
    }

    /** @return string[]|null */
    public function getTargetMuscleGroups(): ?array
    {
        return $this->targetMuscleGroups;
    }

    /** @param string[]|null $targetMuscleGroups */
    public function setTargetMuscleGroups(?array $targetMuscleGroups): self
    {
        $this->targetMuscleGroups = $targetMuscleGroups;

        return $this;
    }

    public function getPlannedAt(): \DateTimeImmutable
    {
        return $this->plannedAt;
    }

    public function setPlannedAt(\DateTimeImmutable $plannedAt): self
    {
        $this->plannedAt = $plannedAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getTargetDistance(): ?float
    {
        return $this->targetDistance;
    }

    public function setTargetDistance(?float $targetDistance): self
    {
        $this->targetDistance = $targetDistance;

        return $this;
    }

    public function getTargetDuration(): ?int
    {
        return $this->targetDuration;
    }

    public function setTargetDuration(?int $targetDuration): self
    {
        $this->targetDuration = $targetDuration;

        return $this;
    }

    public function getTargetCalories(): ?float
    {
        return $this->targetCalories;
    }

    public function setTargetCalories(?float $targetCalories): self
    {
        $this->targetCalories = $targetCalories;

        return $this;
    }

    public function getRewardTiers(): ?array
    {
        return $this->rewardTiers;
    }

    public function setRewardTiers(?array $rewardTiers): self
    {
        $this->rewardTiers = $rewardTiers;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, WorkoutPlanExercise> */
    public function getExercises(): Collection
    {
        return $this->exercises;
    }

    public function addExercise(WorkoutPlanExercise $exercise): self
    {
        if (!$this->exercises->contains($exercise)) {
            $this->exercises->add($exercise);
            $exercise->setWorkoutPlan($this);
        }

        return $this;
    }

    public function removeExercise(WorkoutPlanExercise $exercise): self
    {
        $this->exercises->removeElement($exercise);

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
