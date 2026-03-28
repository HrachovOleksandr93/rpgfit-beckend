<?php

declare(strict_types=1);

namespace App\Application\User\Service;

use App\Application\User\DTO\RegistrationDTO;
use App\Domain\User\Entity\User;
use App\Infrastructure\User\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function register(RegistrationDTO $dto): User
    {
        // Check uniqueness of login
        if ($this->userRepository->findByLogin($dto->login) !== null) {
            throw new ConflictHttpException('A user with this login already exists.');
        }

        // Check uniqueness of display name
        if ($this->userRepository->findByDisplayName($dto->displayName) !== null) {
            throw new ConflictHttpException('A user with this display name already exists.');
        }

        $user = new User();
        $user->setLogin($dto->login);
        $user->setDisplayName($dto->displayName);
        $user->setHeight($dto->height);
        $user->setWeight($dto->weight);
        $user->setWorkoutType($dto->workoutType);
        $user->setActivityLevel($dto->activityLevel);
        $user->setDesiredGoal($dto->desiredGoal);
        $user->setCharacterRace($dto->characterRace);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);

        return $user;
    }
}
