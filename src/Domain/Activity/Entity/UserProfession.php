<?php

declare(strict_types=1);

namespace App\Domain\Activity\Entity;

use App\Domain\User\Entity\User;
use App\Infrastructure\Activity\Repository\UserProfessionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Tracks a user's unlocked profession within a category.
 *
 * Domain layer (Activity bounded context). Each row represents a profession that
 * the user has unlocked. The 'active' flag indicates which profession is currently
 * active in a given category (only one should be active per category per user).
 */
#[ORM\Entity(repositoryClass: UserProfessionRepository::class)]
#[ORM\Table(name: 'user_professions')]
#[ORM\UniqueConstraint(name: 'uniq_user_profession', columns: ['user_id', 'profession_id'])]
#[ORM\Index(name: 'idx_user_profession_active', columns: ['user_id', 'active'])]
class UserProfession
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    /** The user who unlocked this profession */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /** The profession that was unlocked */
    #[ORM\ManyToOne(targetEntity: Profession::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Profession $profession;

    /** When the user unlocked this profession */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $unlockedAt;

    /** Whether this profession is currently active in its category */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $active = true;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->unlockedAt = new \DateTimeImmutable();
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

    public function getProfession(): Profession
    {
        return $this->profession;
    }

    public function setProfession(Profession $profession): self
    {
        $this->profession = $profession;

        return $this;
    }

    public function getUnlockedAt(): \DateTimeImmutable
    {
        return $this->unlockedAt;
    }

    public function setUnlockedAt(\DateTimeImmutable $unlockedAt): self
    {
        $this->unlockedAt = $unlockedAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->profession;
    }
}
