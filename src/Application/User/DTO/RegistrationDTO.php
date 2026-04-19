<?php

declare(strict_types=1);

namespace App\Application\User\DTO;

use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for the user registration form.
 *
 * Maps the JSON body from the mobile app's registration screen to a validated PHP object.
 * Validated by Symfony Validator constraints (NotBlank, Email, Length, Positive).
 *
 * Used by: RegistrationController (populates from JSON) -> RegistrationService (reads fields)
 *
 * Contains both account fields (login/password) and RPG onboarding fields
 * (workout type, activity level, desired goal).
 */
final class RegistrationDTO
{
    #[Assert\NotBlank(message: 'Login is required.')]
    #[Assert\Email(message: 'Login must be a valid email address.')]
    public string $login = '';

    #[Assert\NotBlank(message: 'Password is required.')]
    #[Assert\Length(min: 8, minMessage: 'Password must be at least {{ limit }} characters long.')]
    public string $password = '';

    #[Assert\NotBlank(message: 'Display name is required.')]
    #[Assert\Length(
        min: 3,
        max: 30,
        minMessage: 'Display name must be at least {{ limit }} characters long.',
        maxMessage: 'Display name must not exceed {{ limit }} characters.',
    )]
    public string $displayName = '';

    #[Assert\NotBlank(message: 'Height is required.')]
    #[Assert\Positive(message: 'Height must be a positive number.')]
    public ?float $height = null;

    #[Assert\NotBlank(message: 'Weight is required.')]
    #[Assert\Positive(message: 'Weight must be a positive number.')]
    public ?float $weight = null;

    #[Assert\NotBlank(message: 'Workout type is required.')]
    public ?WorkoutType $workoutType = null;

    #[Assert\NotBlank(message: 'Activity level is required.')]
    public ?ActivityLevel $activityLevel = null;

    #[Assert\NotBlank(message: 'Desired goal is required.')]
    public ?DesiredGoal $desiredGoal = null;
}
