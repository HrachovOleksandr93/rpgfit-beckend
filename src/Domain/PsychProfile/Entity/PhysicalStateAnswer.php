<?php

declare(strict_types=1);

namespace App\Domain\PsychProfile\Entity;

use App\Domain\Battle\Entity\WorkoutSession;
use App\Domain\User\Entity\User;
use App\Infrastructure\PsychProfile\Repository\PhysicalStateAnswerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * A post-workout physical state (session-RPE) answer from Q4 of the psych flow.
 *
 * Domain layer (PsychProfile bounded context). Records the user-reported
 * perceived exertion on a 5-anchor text-only scale after a workout. See
 * spec 2026-04-19 §1.2 for the wording and §2.1 for the schema.
 *
 * Q4 does NOT change PsychStatus — it feeds PsychWorkoutAdapterService
 * to produce deltas for the next plan only.
 *
 * `workoutSession` is nullable because the Q4 can be merged into a daily
 * check-in (within a 2h window) even when the session reference is not
 * known to the client at submit time.
 */
#[ORM\Entity(repositoryClass: PhysicalStateAnswerRepository::class)]
#[ORM\Table(name: 'physical_state_answers')]
#[ORM\Index(name: 'idx_physical_state_user_created', columns: ['user_id', 'created_at'])]
class PhysicalStateAnswer
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /** Optional link to the workout session the Q4 refers to. */
    #[ORM\ManyToOne(targetEntity: WorkoutSession::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?WorkoutSession $workoutSession = null;

    /** 1..5 session-RPE, clamped at the service layer. */
    #[ORM\Column(type: 'integer')]
    private int $rpeScore;

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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getWorkoutSession(): ?WorkoutSession
    {
        return $this->workoutSession;
    }

    public function setWorkoutSession(?WorkoutSession $workoutSession): self
    {
        $this->workoutSession = $workoutSession;

        return $this;
    }

    public function getRpeScore(): int
    {
        return $this->rpeScore;
    }

    public function setRpeScore(int $rpeScore): self
    {
        $this->rpeScore = max(1, min(5, $rpeScore));

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
