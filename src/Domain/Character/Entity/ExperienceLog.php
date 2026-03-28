<?php

declare(strict_types=1);

namespace App\Domain\Character\Entity;

use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Tracks every XP (experience points) gain event for a user.
 *
 * Domain layer (Character bounded context). Each row represents a single XP award.
 * The source field identifies where the XP came from (e.g. "workout", "achievement", "bonus").
 * The description provides human-readable context (e.g. "Completed 10km run").
 *
 * XP is the progression currency in the RPG system -- accumulated XP determines the
 * user's level (level calculation logic to be implemented). Multiple XP events can
 * happen per day from different sources.
 *
 * Data source: will be created by game logic services when workouts are completed
 * or achievements are unlocked. Can also be created manually via admin panel.
 *
 * Indexed by (user_id, earned_at) for efficient chronological queries per user.
 */
#[ORM\Entity(repositoryClass: ExperienceLogRepository::class)]
#[ORM\Table(name: 'experience_logs')]
#[ORM\Index(name: 'idx_experience_user_earned', columns: ['user_id', 'earned_at'])]
class ExperienceLog
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'integer')]
    private int $amount;

    #[ORM\Column(type: 'string', length: 50)]
    private string $source;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $earnedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->earnedAt = new \DateTimeImmutable();
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

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): self
    {
        $this->source = $source;

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

    public function getEarnedAt(): \DateTimeImmutable
    {
        return $this->earnedAt;
    }

    public function setEarnedAt(\DateTimeImmutable $earnedAt): self
    {
        $this->earnedAt = $earnedAt;

        return $this;
    }
}
