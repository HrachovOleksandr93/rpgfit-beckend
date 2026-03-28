<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\User\DTO\RegistrationDTO;
use App\Application\User\Service\RegistrationService;
use App\Domain\User\Enum\ActivityLevel;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\DesiredGoal;
use App\Domain\User\Enum\WorkoutType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API controller for user registration.
 *
 * Receives POST /api/registration with JSON body from the mobile app (iOS/Android).
 * The registration form collects both account data (login, password) and RPG profile
 * data (character race, workout preferences, fitness goals).
 *
 * Flow: Mobile App -> JSON POST -> validate DTO -> RegistrationService -> DB -> JSON response
 *
 * This is a public endpoint (no authentication required).
 */
class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationService $registrationService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Register a new user from the mobile app.
     *
     * Accepts JSON with account credentials and RPG profile fields.
     * Returns the created user data on success (HTTP 201), or validation
     * errors (HTTP 422) / conflict if login/displayName already taken (HTTP 409).
     */
    #[Route('/api/registration', name: 'api_registration', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(
                ['error' => 'Invalid JSON body.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Map raw JSON fields to a validated DTO, converting enum strings to PHP enums
        $dto = new RegistrationDTO();
        $dto->login = $data['login'] ?? '';
        $dto->password = $data['password'] ?? '';
        $dto->displayName = $data['displayName'] ?? '';
        $dto->height = isset($data['height']) ? (float) $data['height'] : null;
        $dto->weight = isset($data['weight']) ? (float) $data['weight'] : null;
        $dto->workoutType = isset($data['workoutType']) ? WorkoutType::tryFrom($data['workoutType']) : null;
        $dto->activityLevel = isset($data['activityLevel']) ? ActivityLevel::tryFrom($data['activityLevel']) : null;
        $dto->desiredGoal = isset($data['desiredGoal']) ? DesiredGoal::tryFrom($data['desiredGoal']) : null;
        $dto->characterRace = isset($data['characterRace']) ? CharacterRace::tryFrom($data['characterRace']) : null;

        // Validate DTO using Symfony Validator constraints defined on RegistrationDTO properties
        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            return $this->json(
                ['errors' => $errors],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Delegate to application service which checks uniqueness, hashes password, and persists
        try {
            $user = $this->registrationService->register($dto);
        } catch (ConflictHttpException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        // Return the newly created user profile back to the mobile app
        return $this->json(
            [
                'id' => $user->getId()->toRfc4122(),
                'login' => $user->getLogin(),
                'displayName' => $user->getDisplayName(),
                'height' => $user->getHeight(),
                'weight' => $user->getWeight(),
                'workoutType' => $user->getWorkoutType()?->value,
                'activityLevel' => $user->getActivityLevel()?->value,
                'desiredGoal' => $user->getDesiredGoal()?->value,
                'characterRace' => $user->getCharacterRace()?->value,
                'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updatedAt' => $user->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ],
            Response::HTTP_CREATED,
        );
    }
}
