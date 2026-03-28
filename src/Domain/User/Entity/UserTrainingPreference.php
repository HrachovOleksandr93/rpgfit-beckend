<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

use App\Domain\User\Enum\Lifestyle;
use App\Domain\User\Enum\TrainingFrequency;
use App\Domain\User\Enum\WorkoutType;
use App\Infrastructure\User\Repository\UserTrainingPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Stores detailed training preferences collected during onboarding.
 *
 * One-to-one relationship with User. Extracted from User entity to keep
 * core profile fields separate from training-specific configuration.
 *
 * Fields:
 * - trainingFrequency: how often the user trains per week
 * - lifestyle: daily activity level outside of training
 * - primaryTrainingStyle: detailed workout style for training generation
 * - preferredWorkouts: array of specific workout slugs the user selected
 */
#[ORM\Entity(repositoryClass: UserTrainingPreferenceRepository::class)]
#[ORM\Table(name: 'user_training_preferences')]
class UserTrainingPreference
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', unique: true, nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: TrainingFrequency::class)]
    private ?TrainingFrequency $trainingFrequency = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: Lifestyle::class)]
    private ?Lifestyle $lifestyle = null;

    // Detailed training style for workout generation (strength/cardio/crossfit/gymnastics/martial_arts/yoga)
    #[ORM\Column(type: 'string', length: 30, nullable: true, enumType: WorkoutType::class)]
    private ?WorkoutType $primaryTrainingStyle = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $preferredWorkouts = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
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

    public function getTrainingFrequency(): ?TrainingFrequency
    {
        return $this->trainingFrequency;
    }

    public function setTrainingFrequency(?TrainingFrequency $trainingFrequency): self
    {
        $this->trainingFrequency = $trainingFrequency;

        return $this;
    }

    public function getLifestyle(): ?Lifestyle
    {
        return $this->lifestyle;
    }

    public function setLifestyle(?Lifestyle $lifestyle): self
    {
        $this->lifestyle = $lifestyle;

        return $this;
    }

    public function getPrimaryTrainingStyle(): ?WorkoutType
    {
        return $this->primaryTrainingStyle;
    }

    public function setPrimaryTrainingStyle(?WorkoutType $primaryTrainingStyle): self
    {
        $this->primaryTrainingStyle = $primaryTrainingStyle;

        return $this;
    }

    public function getPreferredWorkouts(): ?array
    {
        return $this->preferredWorkouts;
    }

    public function setPreferredWorkouts(?array $preferredWorkouts): self
    {
        $this->preferredWorkouts = $preferredWorkouts;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('Training Preferences for %s', $this->user);
    }
}
