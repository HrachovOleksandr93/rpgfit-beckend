<?php

declare(strict_types=1);

namespace App\Controller\Test;

use App\Application\Test\DTO\LogWorkoutRequest;
use App\Application\Test\DTO\SimulateStreamRequest;
use App\Application\Test\Service\AdminActionLogService;
use App\Application\Test\Service\TargetUserResolver;
use App\Application\Test\Service\TestHarnessGate;
use App\Application\Test\Service\TestHarnessRateLimiter;
use App\Application\Test\Service\WorkoutTestService;
use App\Domain\User\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Workout logging + synthetic stream (spec §3.4).
 */
#[Route('/api/test')]
#[IsGranted('ROLE_TESTER')]
final class WorkoutTestController extends AbstractTestController
{
    public function __construct(
        TargetUserResolver $resolver,
        AdminActionLogService $audit,
        TestHarnessGate $gate,
        TestHarnessRateLimiter $rateLimiter,
        private readonly WorkoutTestService $workoutTestService,
    ) {
        parent::__construct($resolver, $audit, $gate, $rateLimiter);
    }

    #[Route('/workout/log', name: 'api_test_workout_log', methods: ['POST'])]
    public function log(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'workout.log');

        $target = $this->resolveTarget($request, $currentUser);
        $dto = LogWorkoutRequest::fromArray($this->decodeBody($request));

        $performedAt = null;
        if ($dto->performedAt !== null) {
            try {
                $performedAt = new \DateTimeImmutable($dto->performedAt);
            } catch (\Exception) {
                $performedAt = null;
            }
        }

        $result = $this->workoutTestService->logWorkout(
            $target,
            $dto->workoutType,
            $dto->durationMinutes,
            $dto->calories,
            $dto->distance,
            $performedAt,
        );
        $auditId = $this->audit($currentUser, $target, 'workout.log', $result);

        return $this->json($this->envelope($currentUser, $target, $auditId, $result));
    }

    #[Route('/workout/simulate-stream', name: 'api_test_workout_simulate_stream', methods: ['POST'])]
    public function simulateStream(Request $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->assertEnabled();
        $this->enforceRateLimit($currentUser, 'workout.simulate_stream');

        $target = $this->resolveTarget($request, $currentUser);
        $dto = SimulateStreamRequest::fromArray($this->decodeBody($request));

        $result = $this->workoutTestService->simulateWorkoutStream(
            $target,
            $dto->samples,
            $dto->durationSeconds,
            $dto->heartRate,
        );
        $auditId = $this->audit($currentUser, $target, 'workout.simulate_stream', $result);

        return $this->json($this->envelope($currentUser, $target, $auditId, $result));
    }
}
