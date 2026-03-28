<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use App\Infrastructure\User\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(normalizationContext: ['groups' => ['user:read']]),
        new GetCollection(normalizationContext: ['groups' => ['user:read']]),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[Groups(['user:read'])]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Groups(['user:read'])]
    private string $login;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', length: 30, unique: true)]
    #[Groups(['user:read'])]
    private string $displayName;

    #[ORM\Column(type: 'float')]
    #[Groups(['user:read'])]
    private float $height;

    #[ORM\Column(type: 'float')]
    #[Groups(['user:read'])]
    private float $weight;

    #[ORM\Column(type: 'string', length: 20, enumType: WorkoutType::class)]
    #[Groups(['user:read'])]
    private WorkoutType $workoutType;

    #[ORM\Column(type: 'string', length: 20, enumType: ActivityLevel::class)]
    #[Groups(['user:read'])]
    private ActivityLevel $activityLevel;

    #[ORM\Column(type: 'string', length: 20, enumType: DesiredGoal::class)]
    #[Groups(['user:read'])]
    private DesiredGoal $desiredGoal;

    #[ORM\Column(type: 'string', length: 20, enumType: CharacterRace::class)]
    #[Groups(['user:read'])]
    private CharacterRace $characterRace;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['user:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['user:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function setHeight(float $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): self
    {
        $this->weight = $weight;

        return $this;
    }

    public function getWorkoutType(): WorkoutType
    {
        return $this->workoutType;
    }

    public function setWorkoutType(WorkoutType $workoutType): self
    {
        $this->workoutType = $workoutType;

        return $this;
    }

    public function getActivityLevel(): ActivityLevel
    {
        return $this->activityLevel;
    }

    public function setActivityLevel(ActivityLevel $activityLevel): self
    {
        $this->activityLevel = $activityLevel;

        return $this;
    }

    public function getDesiredGoal(): DesiredGoal
    {
        return $this->desiredGoal;
    }

    public function setDesiredGoal(DesiredGoal $desiredGoal): self
    {
        $this->desiredGoal = $desiredGoal;

        return $this;
    }

    public function getCharacterRace(): CharacterRace
    {
        return $this->characterRace;
    }

    public function setCharacterRace(CharacterRace $characterRace): self
    {
        $this->characterRace = $characterRace;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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

    // UserInterface methods

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase
    }

    public function getUserIdentifier(): string
    {
        return $this->login;
    }
}
