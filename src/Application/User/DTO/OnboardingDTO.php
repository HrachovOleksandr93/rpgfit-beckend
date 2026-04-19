<?php

declare(strict_types=1);

namespace App\Application\User\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object for the onboarding questionnaire.
 *
 * Maps the JSON body from the mobile app's onboarding flow to a validated PHP object.
 * All fields are collected in a single step on the client and sent as one POST request.
 *
 * Enum values (gender, workoutType, trainingFrequency, lifestyle)
 * are received as strings and validated/converted by the OnboardingController.
 *
 * Used by: OnboardingController (populates from JSON) -> OnboardingService (reads fields)
 */
final class OnboardingDTO
{
    #[Assert\NotBlank(message: 'Display name is required.')]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_]+$/',
        message: 'Display name must contain only Latin letters, digits, and underscores.',
    )]
    #[Assert\Length(
        min: 3,
        max: 30,
        minMessage: 'Display name must be at least {{ limit }} characters long.',
        maxMessage: 'Display name must not exceed {{ limit }} characters.',
    )]
    public string $displayName = '';

    #[Assert\NotBlank(message: 'Height is required.')]
    #[Assert\Positive(message: 'Height must be a positive number.')]
    #[Assert\Range(
        min: 50,
        max: 300,
        notInRangeMessage: 'Height must be between {{ min }} and {{ max }} cm.',
    )]
    public ?float $height = null;

    #[Assert\NotBlank(message: 'Weight is required.')]
    #[Assert\Positive(message: 'Weight must be a positive number.')]
    #[Assert\Range(
        min: 20,
        max: 500,
        notInRangeMessage: 'Weight must be between {{ min }} and {{ max }} kg.',
    )]
    public ?float $weight = null;

    // Validated as string, converted to Gender enum in controller
    #[Assert\NotBlank(message: 'Gender is required.')]
    public string $gender = '';

    // Validated as string, converted to TrainingFrequency enum in controller
    #[Assert\NotBlank(message: 'Training frequency is required.')]
    public string $trainingFrequency = '';

    // Validated as string, converted to WorkoutType enum in controller
    #[Assert\NotBlank(message: 'Workout type is required.')]
    public string $workoutType = '';

    // Validated as string, converted to Lifestyle enum in controller
    #[Assert\NotBlank(message: 'Lifestyle is required.')]
    public string $lifestyle = '';

    // Multi-select array of preferred workout slugs
    #[Assert\NotBlank(message: 'Preferred workouts are required.')]
    #[Assert\Count(
        min: 1,
        minMessage: 'You must select at least {{ limit }} preferred workout.',
    )]
    public array $preferredWorkouts = [];
}
