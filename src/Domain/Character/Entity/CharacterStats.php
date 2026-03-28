<?php

declare(strict_types=1);

namespace App\Domain\Character\Entity;

use App\Domain\User\Entity\User;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * RPG character stats for a user -- 1:1 relationship with User.
 *
 * Domain layer (Character bounded context). Stores the current values of the three
 * core RPG stats: Strength (STR), Dexterity (DEX), Constitution (CON).
 *
 * These stats are the gamification layer of the fitness app. They will be updated
 * by game logic when a user completes workouts (via ExerciseStatReward configuration).
 * Currently the values can also be managed manually via the Sonata admin panel.
 *
 * All stats start at 0 and increase as the user trains. The stat gains per exercise
 * are defined in ExerciseStatReward (admin-configurable).
 *
 * Related: User (owner), ExerciseStatReward (defines stat point rewards per exercise)
 */
#[ORM\Entity(repositoryClass: CharacterStatsRepository::class)]
#[ORM\Table(name: 'character_stats')]
#[ORM\HasLifecycleCallbacks]
class CharacterStats
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private User $user;

    #[ORM\Column(type: 'integer')]
    private int $strength = 0;

    #[ORM\Column(type: 'integer')]
    private int $dexterity = 0;

    #[ORM\Column(type: 'integer')]
    private int $constitution = 0;

    /** Current character level, derived from totalXp via the leveling curve. */
    #[ORM\Column(type: 'integer', options: ['default' => 1])]
    private int $level = 1;

    /** Cached cumulative XP earned by this character across all time. */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $totalXp = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getStrength(): int
    {
        return $this->strength;
    }

    public function setStrength(int $strength): self
    {
        $this->strength = $strength;

        return $this;
    }

    public function getDexterity(): int
    {
        return $this->dexterity;
    }

    public function setDexterity(int $dexterity): self
    {
        $this->dexterity = $dexterity;

        return $this;
    }

    public function getConstitution(): int
    {
        return $this->constitution;
    }

    public function setConstitution(int $constitution): self
    {
        $this->constitution = $constitution;

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getTotalXp(): int
    {
        return $this->totalXp;
    }

    public function setTotalXp(int $totalXp): self
    {
        $this->totalXp = $totalXp;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
