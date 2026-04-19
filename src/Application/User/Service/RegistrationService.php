<?php

declare(strict_types=1);

namespace App\Application\User\Service;

use App\Application\User\DTO\RegistrationDTO;
use App\Domain\User\Entity\User;
use App\Infrastructure\User\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Application service responsible for user registration business logic.
 *
 * Application layer (User bounded context). Orchestrates the registration flow:
 * 1. Validates login (email) and display name uniqueness against the database
 * 2. Creates a new User entity with all profile data from the DTO
 * 3. Hashes the password using Symfony's password hasher (bcrypt/argon2)
 * 4. Persists the user to the database
 *
 * Called by: RegistrationController (which handles HTTP/JSON concerns)
 * Data source: RegistrationDTO (mapped from mobile app JSON)
 * Data destination: User entity persisted to `users` table via UserRepository
 *
 * Throws ConflictHttpException if login or display name is already taken,
 * which the controller translates to HTTP 409.
 */
final class RegistrationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Register a new user from the mobile app registration form.
     *
     * @param RegistrationDTO $dto Validated registration data from the mobile app
     * @return User The newly created and persisted user entity
     * @throws ConflictHttpException If login or display name already exists
     */
    public function register(RegistrationDTO $dto): User
    {
        // Check uniqueness of login (email) -- must be unique across all users
        if ($this->userRepository->findByLogin($dto->login) !== null) {
            throw new ConflictHttpException('A user with this login already exists.');
        }

        // Check uniqueness of display name -- used as the public RPG character name
        if ($this->userRepository->findByDisplayName($dto->displayName) !== null) {
            throw new ConflictHttpException('A user with this display name already exists.');
        }

        // Create entity and populate from DTO (account + physical + RPG profile data)
        $user = new User();
        $user->setLogin($dto->login);
        $user->setDisplayName($dto->displayName);
        $user->setHeight($dto->height);
        $user->setWeight($dto->weight);
        $user->setWorkoutType($dto->workoutType);
        $user->setActivityLevel($dto->activityLevel);
        $user->setDesiredGoal($dto->desiredGoal);

        // Users registered via the standard flow have all profile data, so mark onboarding as done
        $user->setOnboardingCompleted(true);

        // Hash password using Symfony's secure hasher before storing
        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        // Persist to database (flush immediately)
        $this->userRepository->save($user);

        return $user;
    }
}
