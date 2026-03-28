<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\Gender;
use App\Domain\User\Enum\WorkoutType;
use App\Infrastructure\User\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

/**
 * Central user entity -- represents both the authenticated account and the RPG character.
 *
 * Domain layer (User bounded context). This is the core identity of the system.
 * Every other entity (CharacterStats, HealthDataPoint, WorkoutLog, ExperienceLog) references User.
 *
 * Combines:
 * - Authentication data: login (email), hashed password, roles (Symfony Security)
 * - Physical profile: height, weight, gender (from registration/onboarding)
 * - RPG profile: characterRace, workoutType preference, activityLevel, desiredGoal
 * - Onboarding state: onboardingCompleted flag, training preferences
 *
 * Data source: created via RegistrationController or OAuthController (mobile app POST).
 * Exposed via: ProfileController (GET), UserController (GET), ApiPlatform (admin API), SonataAdmin.
 *
 * Implements UserInterface for Symfony Security (JWT authentication via lexik/jwt-auth).
 *
 * Fields that were previously NOT NULL are now nullable to support the OAuth flow,
 * where a user is created before completing the onboarding questionnaire.
 */
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

    // Nullable for OAuth flow: user may not have a display name until onboarding
    #[ORM\Column(type: 'string', length: 30, unique: true, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $displayName = null;

    // Nullable for OAuth flow: set during onboarding
    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['user:read'])]
    private ?float $height = null;

    // Nullable for OAuth flow: set during onboarding
    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['user:read'])]
    private ?float $weight = null;

    // Nullable for OAuth flow: set during onboarding
    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: WorkoutType::class)]
    #[Groups(['user:read'])]
    private ?WorkoutType $workoutType = null;

    // Nullable for OAuth flow: set during onboarding
    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: ActivityLevel::class)]
    #[Groups(['user:read'])]
    private ?ActivityLevel $activityLevel = null;

    // Nullable for OAuth flow: set during onboarding
    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: DesiredGoal::class)]
    #[Groups(['user:read'])]
    private ?DesiredGoal $desiredGoal = null;

    // Nullable for OAuth flow: set during onboarding
    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: CharacterRace::class)]
    #[Groups(['user:read'])]
    private ?CharacterRace $characterRace = null;

    // New field: user gender, collected during onboarding
    #[ORM\Column(type: 'string', length: 10, nullable: true, enumType: Gender::class)]
    #[Groups(['user:read'])]
    private ?Gender $gender = null;

    // New field: tracks whether the onboarding questionnaire has been completed
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read'])]
    private bool $onboardingCompleted = false;

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

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getHeight(): ?float
    {
        return $this->height;
    }

    public function setHeight(?float $height): self
    {
        $this->height = $height;

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

    public function getWorkoutType(): ?WorkoutType
    {
        return $this->workoutType;
    }

    public function setWorkoutType(?WorkoutType $workoutType): self
    {
        $this->workoutType = $workoutType;

        return $this;
    }

    public function getActivityLevel(): ?ActivityLevel
    {
        return $this->activityLevel;
    }

    public function setActivityLevel(?ActivityLevel $activityLevel): self
    {
        $this->activityLevel = $activityLevel;

        return $this;
    }

    public function getDesiredGoal(): ?DesiredGoal
    {
        return $this->desiredGoal;
    }

    public function setDesiredGoal(?DesiredGoal $desiredGoal): self
    {
        $this->desiredGoal = $desiredGoal;

        return $this;
    }

    public function getCharacterRace(): ?CharacterRace
    {
        return $this->characterRace;
    }

    public function setCharacterRace(?CharacterRace $characterRace): self
    {
        $this->characterRace = $characterRace;

        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function isOnboardingCompleted(): bool
    {
        return $this->onboardingCompleted;
    }

    public function setOnboardingCompleted(bool $onboardingCompleted): self
    {
        $this->onboardingCompleted = $onboardingCompleted;

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

    /** Doctrine lifecycle callback: auto-update timestamp on every entity change. */
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- Symfony Security UserInterface methods ---

    /** All users have ROLE_USER. Admin roles are managed separately. */
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

    public function __toString(): string
    {
        return $this->displayName ?? $this->login;
    }
}
