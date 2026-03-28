<?php

declare(strict_types=1);

namespace App\Application\User\Service;

use App\Application\Character\Service\StatCalculationService;
use App\Application\User\DTO\OnboardingDTO;
use App\Domain\Character\Entity\CharacterStats;
use App\Domain\User\Entity\User;
use App\Domain\User\Entity\UserTrainingPreference;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\Gender;
use App\Domain\User\Enum\Lifestyle;
use App\Domain\User\Enum\TrainingFrequency;
use App\Domain\User\Enum\WorkoutType;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\User\Repository\UserRepository;
use App\Infrastructure\User\Repository\UserTrainingPreferenceRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Application service for completing the onboarding questionnaire.
 *
 * Orchestrates the full onboarding flow:
 * 1. Validates display name uniqueness
 * 2. Updates the User entity with profile data from the DTO
 * 3. Creates UserTrainingPreference with training-specific data
 * 4. Calculates initial RPG stats via StatCalculationService
 * 5. Creates and persists CharacterStats
 * 6. Marks onboarding as completed
 *
 * Called by OnboardingController after DTO validation.
 */
final class OnboardingService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CharacterStatsRepository $characterStatsRepository,
        private readonly UserTrainingPreferenceRepository $trainingPreferenceRepository,
        private readonly StatCalculationService $statCalculationService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Complete the onboarding for a user by setting profile fields and calculating stats.
     *
     * @throws ConflictHttpException If the chosen display name is already taken
     */
    public function completeOnboarding(User $user, OnboardingDTO $dto): User
    {
        // Step 1: Check display name uniqueness against existing users
        $existingUser = $this->userRepository->findByDisplayName($dto->displayName);
        if ($existingUser !== null && $existingUser->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            throw new ConflictHttpException('A user with this display name already exists.');
        }

        // Step 2: Update user entity with core profile fields
        $user->setDisplayName($dto->displayName);
        $user->setHeight($dto->height);
        $user->setWeight($dto->weight);
        $user->setGender(Gender::from($dto->gender));
        $user->setCharacterRace(CharacterRace::from($dto->characterRace));
        $user->setWorkoutType(WorkoutType::from($dto->workoutType));

        // Step 3: Create training preferences (separate entity)
        $trainingPreference = new UserTrainingPreference();
        $trainingPreference->setUser($user);
        $trainingPreference->setTrainingFrequency(TrainingFrequency::from($dto->trainingFrequency));
        $trainingPreference->setLifestyle(Lifestyle::from($dto->lifestyle));
        $trainingPreference->setPrimaryTrainingStyle(WorkoutType::from($dto->workoutType));
        $trainingPreference->setPreferredWorkouts($dto->preferredWorkouts);
        $this->trainingPreferenceRepository->save($trainingPreference, false);

        // Step 4: Calculate initial RPG stats based on workout type, lifestyle, frequency
        $stats = $this->statCalculationService->calculateInitialStats(
            WorkoutType::from($dto->workoutType),
            Lifestyle::from($dto->lifestyle),
            TrainingFrequency::from($dto->trainingFrequency),
        );

        // Step 5: Create and persist CharacterStats entity (1:1 with User)
        $characterStats = new CharacterStats();
        $characterStats->setUser($user);
        $characterStats->setStrength($stats['strength']);
        $characterStats->setDexterity($stats['dexterity']);
        $characterStats->setConstitution($stats['constitution']);
        $this->characterStatsRepository->save($characterStats);

        // Step 6: Mark onboarding as completed
        $user->setOnboardingCompleted(true);

        // Step 7: Persist updated user
        $this->userRepository->save($user);

        return $user;
    }
}
