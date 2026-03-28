<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\User\DTO\OnboardingDTO;
use App\Application\User\Service\OnboardingService;
use App\Domain\User\Entity\User;
use App\Domain\User\Enum\CharacterRace;
use App\Domain\User\Enum\Gender;
use App\Domain\User\Enum\Lifestyle;
use App\Domain\User\Enum\TrainingFrequency;
use App\Domain\User\Enum\WorkoutType;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * API controller for completing the onboarding questionnaire.
 *
 * Receives POST /api/onboarding with JSON body from the mobile app after OAuth login.
 * The onboarding collects all profile data (display name, physical stats, RPG preferences)
 * and calculates initial character stats.
 *
 * Requires JWT authentication. Can only be called once per user (returns 409 if already completed).
 *
 * Flow: Mobile App (with JWT) -> JSON POST -> validate DTO + enums -> OnboardingService -> DB -> JSON response
 */
class OnboardingController extends AbstractController
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
        private readonly ValidatorInterface $validator,
        private readonly CharacterStatsRepository $characterStatsRepository,
    ) {
    }

    /**
     * Complete onboarding for the authenticated user.
     *
     * Validates all input fields including enum values, then delegates to OnboardingService
     * to update the user profile and calculate initial RPG stats.
     */
    #[Route('/api/onboarding', name: 'api_onboarding', methods: ['POST'])]
    public function onboarding(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        // Prevent double onboarding
        if ($user->isOnboardingCompleted()) {
            return $this->json(
                ['error' => 'Onboarding already completed.'],
                Response::HTTP_CONFLICT,
            );
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(
                ['error' => 'Invalid JSON body.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Populate DTO from JSON
        $dto = new OnboardingDTO();
        $dto->displayName = $data['displayName'] ?? '';
        $dto->height = isset($data['height']) ? (float) $data['height'] : null;
        $dto->weight = isset($data['weight']) ? (float) $data['weight'] : null;
        $dto->gender = $data['gender'] ?? '';
        $dto->characterRace = $data['characterRace'] ?? '';
        $dto->trainingFrequency = $data['trainingFrequency'] ?? '';
        $dto->workoutType = $data['workoutType'] ?? '';
        $dto->lifestyle = $data['lifestyle'] ?? '';
        $dto->preferredWorkouts = $data['preferredWorkouts'] ?? [];

        // Validate DTO constraints (NotBlank, Length, Range, etc.)
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

        // Validate enum values: ensure strings map to valid PHP enum cases
        $enumErrors = [];

        if (Gender::tryFrom($dto->gender) === null) {
            $enumErrors['gender'][] = 'Invalid gender value.';
        }
        if (CharacterRace::tryFrom($dto->characterRace) === null) {
            $enumErrors['characterRace'][] = 'Invalid character race value.';
        }
        if (WorkoutType::tryFrom($dto->workoutType) === null) {
            $enumErrors['workoutType'][] = 'Invalid workout type value.';
        }
        if (TrainingFrequency::tryFrom($dto->trainingFrequency) === null) {
            $enumErrors['trainingFrequency'][] = 'Invalid training frequency value.';
        }
        if (Lifestyle::tryFrom($dto->lifestyle) === null) {
            $enumErrors['lifestyle'][] = 'Invalid lifestyle value.';
        }

        if (!empty($enumErrors)) {
            return $this->json(
                ['errors' => $enumErrors],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Delegate to service to update user fields, calculate stats, and persist
        try {
            $user = $this->onboardingService->completeOnboarding($user, $dto);
        } catch (ConflictHttpException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        // Load the newly created stats for the response
        $stats = $this->characterStatsRepository->findByUser($user);

        return $this->json([
            'id' => $user->getId()->toRfc4122(),
            'login' => $user->getLogin(),
            'displayName' => $user->getDisplayName(),
            'gender' => $user->getGender()?->value,
            'height' => $user->getHeight(),
            'weight' => $user->getWeight(),
            'characterRace' => $user->getCharacterRace()?->value,
            'workoutType' => $user->getWorkoutType()?->value,
            'trainingFrequency' => $user->getTrainingFrequency()?->value,
            'lifestyle' => $user->getLifestyle()?->value,
            'activityLevel' => $user->getActivityLevel()?->value,
            'desiredGoal' => $user->getDesiredGoal()?->value,
            'preferredWorkouts' => $user->getPreferredWorkouts(),
            'onboardingCompleted' => $user->isOnboardingCompleted(),
            'stats' => $stats !== null ? [
                'strength' => $stats->getStrength(),
                'dexterity' => $stats->getDexterity(),
                'constitution' => $stats->getConstitution(),
            ] : null,
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $user->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}
