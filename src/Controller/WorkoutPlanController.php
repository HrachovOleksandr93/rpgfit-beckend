<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Character\Service\XpAwardService;
use App\Application\Workout\Service\WorkoutPlanGeneratorService;
use App\Domain\Character\Entity\ExperienceLog;
use App\Domain\User\Entity\User;
use App\Domain\Workout\Entity\WorkoutPlan;
use App\Domain\Workout\Entity\WorkoutPlanExercise;
use App\Domain\Workout\Entity\WorkoutPlanExerciseLog;
use App\Domain\Workout\Enum\WorkoutPlanStatus;
use App\Infrastructure\Character\Repository\CharacterStatsRepository;
use App\Infrastructure\Character\Repository\ExperienceLogRepository;
use App\Infrastructure\Workout\Repository\WorkoutPlanExerciseLogRepository;
use App\Infrastructure\Workout\Repository\WorkoutPlanExerciseRepository;
use App\Infrastructure\Workout\Repository\WorkoutPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * API controller for workout plan management.
 *
 * Provides endpoints for generating, listing, viewing, starting, completing,
 * and logging progress on workout plans. All endpoints require JWT authentication
 * and enforce ownership -- users can only access their own plans.
 *
 * Endpoints:
 * - POST /api/workout/generate          Generate a new personalized workout plan
 * - GET  /api/workout/plans             List user's plans with optional filters
 * - GET  /api/workout/plans/{id}        Get plan details with exercises
 * - POST /api/workout/plans/{id}/start  Start a pending plan
 * - POST /api/workout/plans/{id}/complete  Complete a plan and award XP
 * - POST /api/workout/plans/{id}/skip   Skip a plan
 * - POST /api/workout/plans/{planId}/exercises/{exerciseId}/log  Log a set
 */
class WorkoutPlanController extends AbstractController
{
    public function __construct(
        private readonly WorkoutPlanGeneratorService $generatorService,
        private readonly WorkoutPlanRepository $workoutPlanRepository,
        private readonly WorkoutPlanExerciseRepository $workoutPlanExerciseRepository,
        private readonly WorkoutPlanExerciseLogRepository $exerciseLogRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ExperienceLogRepository $experienceLogRepository,
        private readonly CharacterStatsRepository $characterStatsRepository,
    ) {
    }

    /**
     * Generate a new personalized workout plan for the authenticated user.
     *
     * Accepts optional activity category and target date. Delegates to
     * WorkoutPlanGeneratorService which selects exercises based on the user's
     * preferences, training history, and difficulty level.
     */
    #[Route('/api/workout/generate', name: 'api_workout_generate', methods: ['POST'])]
    public function generate(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $activityCategory = $data['activityCategory'] ?? null;
        $dateStr = $data['date'] ?? null;
        $date = $dateStr !== null ? new \DateTimeImmutable($dateStr) : null;

        $plan = $this->generatorService->generatePlan($user, $activityCategory, $date);

        return $this->json(['plan' => $this->serializePlan($plan)]);
    }

    /**
     * List the authenticated user's workout plans.
     *
     * Supports filtering by status (pending, in_progress, completed, skipped)
     * and pagination via limit/offset query parameters.
     */
    #[Route('/api/workout/plans', name: 'api_workout_plans_list', methods: ['GET'])]
    public function listPlans(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $status = $request->query->get('status');
        $limit = $request->query->getInt('limit', 20);
        $offset = $request->query->getInt('offset', 0);

        $qb = $this->entityManager->createQueryBuilder()
            ->select('wp')
            ->from(WorkoutPlan::class, 'wp')
            ->where('wp.user = :user')
            ->setParameter('user', $user)
            ->orderBy('wp.plannedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($status !== null) {
            $statusEnum = WorkoutPlanStatus::tryFrom($status);
            if ($statusEnum !== null) {
                $qb->andWhere('wp.status = :status')
                    ->setParameter('status', $statusEnum);
            }
        }

        $plans = $qb->getQuery()->getResult();

        $serialized = array_map(fn(WorkoutPlan $p) => $this->serializePlanSummary($p), $plans);

        return $this->json(['plans' => $serialized]);
    }

    /**
     * Get details of a specific workout plan including all exercises.
     *
     * Returns 403 if the plan belongs to a different user.
     */
    #[Route('/api/workout/plans/{id}', name: 'api_workout_plan_detail', methods: ['GET'])]
    public function getPlan(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $plan = $this->workoutPlanRepository->find($id);

        if ($plan === null) {
            return $this->json(['error' => 'Plan not found.'], Response::HTTP_NOT_FOUND);
        }

        // Enforce ownership: only the plan owner can access it
        if ($plan->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        return $this->json(['plan' => $this->serializePlan($plan)]);
    }

    /**
     * Start a pending workout plan. Sets status to in_progress and records startedAt.
     */
    #[Route('/api/workout/plans/{id}/start', name: 'api_workout_plan_start', methods: ['POST'])]
    public function startPlan(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $plan = $this->workoutPlanRepository->find($id);

        if ($plan === null) {
            return $this->json(['error' => 'Plan not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($plan->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        if ($plan->getStatus() !== WorkoutPlanStatus::Pending) {
            return $this->json(
                ['error' => 'Plan can only be started from pending status.'],
                Response::HTTP_CONFLICT,
            );
        }

        $plan->setStatus(WorkoutPlanStatus::InProgress);
        $plan->setStartedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->json(['plan' => $this->serializePlan($plan)]);
    }

    /**
     * Complete a workout plan and award XP based on completed exercises vs reward tiers.
     *
     * Determines which reward tier the user achieved based on how many exercises
     * they logged sets for, then awards the corresponding XP amount via ExperienceLog.
     */
    #[Route('/api/workout/plans/{id}/complete', name: 'api_workout_plan_complete', methods: ['POST'])]
    public function completePlan(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $plan = $this->workoutPlanRepository->find($id);

        if ($plan === null) {
            return $this->json(['error' => 'Plan not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($plan->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        if ($plan->getStatus() !== WorkoutPlanStatus::InProgress) {
            return $this->json(
                ['error' => 'Plan can only be completed from in_progress status.'],
                Response::HTTP_CONFLICT,
            );
        }

        $plan->setStatus(WorkoutPlanStatus::Completed);
        $plan->setCompletedAt(new \DateTimeImmutable());

        // Calculate XP reward based on completed exercises and reward tiers
        $xpAwarded = $this->calculateXpReward($plan);

        // Award XP by creating an ExperienceLog entry and updating character stats
        if ($xpAwarded > 0) {
            $log = new ExperienceLog();
            $log->setUser($user);
            $log->setAmount($xpAwarded);
            $log->setSource('workout_plan');
            $log->setDescription('Completed workout: ' . $plan->getName());
            $this->entityManager->persist($log);

            // Update character stats if they exist
            $stats = $this->characterStatsRepository->findByUser($user);
            if ($stats !== null) {
                $stats->setTotalXp($stats->getTotalXp() + $xpAwarded);
                $this->entityManager->persist($stats);
            }
        }

        $this->entityManager->flush();

        return $this->json([
            'plan' => $this->serializePlanSummary($plan),
            'xpAwarded' => $xpAwarded,
        ]);
    }

    /**
     * Log a set for a specific exercise within a workout plan.
     *
     * Records the actual reps, weight, and optional notes for one set.
     * The plan must be in_progress status.
     */
    #[Route('/api/workout/plans/{planId}/exercises/{exerciseId}/log', name: 'api_workout_exercise_log', methods: ['POST'])]
    public function logExerciseSet(
        string $planId,
        string $exerciseId,
        Request $request,
        #[CurrentUser] User $user,
    ): JsonResponse {
        $plan = $this->workoutPlanRepository->find($planId);

        if ($plan === null) {
            return $this->json(['error' => 'Plan not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($plan->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        // Find the plan exercise by ID
        $planExercise = $this->workoutPlanExerciseRepository->find($exerciseId);

        if ($planExercise === null) {
            return $this->json(['error' => 'Exercise not found in plan.'], Response::HTTP_NOT_FOUND);
        }

        // Verify the exercise belongs to this plan
        if ($planExercise->getWorkoutPlan()->getId()->toRfc4122() !== $plan->getId()->toRfc4122()) {
            return $this->json(['error' => 'Exercise does not belong to this plan.'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['setNumber'])) {
            return $this->json(
                ['error' => 'setNumber is required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        // Create the exercise log entry for this set
        $log = new WorkoutPlanExerciseLog();
        $log->setPlanExercise($planExercise);
        $log->setSetNumber((int) $data['setNumber']);
        $log->setReps(isset($data['reps']) ? (int) $data['reps'] : null);
        $log->setWeight(isset($data['weight']) ? (float) $data['weight'] : null);
        $log->setDuration(isset($data['duration']) ? (int) $data['duration'] : null);
        $log->setNotes($data['notes'] ?? null);

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $this->json([
            'id' => $log->getId()->toRfc4122(),
            'setNumber' => $log->getSetNumber(),
            'reps' => $log->getReps(),
            'weight' => $log->getWeight(),
            'duration' => $log->getDuration(),
            'notes' => $log->getNotes(),
        ]);
    }

    /**
     * Skip a workout plan. Sets status to skipped.
     * Plans can be skipped from pending or in_progress status.
     */
    #[Route('/api/workout/plans/{id}/skip', name: 'api_workout_plan_skip', methods: ['POST'])]
    public function skipPlan(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $plan = $this->workoutPlanRepository->find($id);

        if ($plan === null) {
            return $this->json(['error' => 'Plan not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($plan->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        if (!in_array($plan->getStatus(), [WorkoutPlanStatus::Pending, WorkoutPlanStatus::InProgress], true)) {
            return $this->json(
                ['error' => 'Plan can only be skipped from pending or in_progress status.'],
                Response::HTTP_CONFLICT,
            );
        }

        $plan->setStatus(WorkoutPlanStatus::Skipped);

        $this->entityManager->flush();

        return $this->json(['plan' => $this->serializePlanSummary($plan)]);
    }

    // ========================================================================
    // Serialization Helpers
    // ========================================================================

    /**
     * Serialize a workout plan with full exercise details for API response.
     */
    private function serializePlan(WorkoutPlan $plan): array
    {
        $exercises = [];
        foreach ($plan->getExercises() as $planExercise) {
            $exercise = $planExercise->getExercise();
            $exercises[] = [
                'id' => $planExercise->getId()->toRfc4122(),
                'orderIndex' => $planExercise->getOrderIndex(),
                'exercise' => [
                    'id' => $exercise->getId()->toRfc4122(),
                    'name' => $exercise->getName(),
                    'slug' => $exercise->getSlug(),
                    'primaryMuscle' => $exercise->getPrimaryMuscle()->value,
                    'equipment' => $exercise->getEquipment()->value,
                    'image' => null, // MediaFile serialization not implemented here
                ],
                'sets' => $planExercise->getSets(),
                'repsMin' => $planExercise->getRepsMin(),
                'repsMax' => $planExercise->getRepsMax(),
                'restSeconds' => $planExercise->getRestSeconds(),
                'notes' => $planExercise->getNotes(),
            ];
        }

        return [
            'id' => $plan->getId()->toRfc4122(),
            'name' => $plan->getName(),
            'status' => $plan->getStatus()->value,
            'activityType' => $plan->getActivityType(),
            'targetMuscleGroups' => $plan->getTargetMuscleGroups(),
            'plannedAt' => $plan->getPlannedAt()->format(\DateTimeInterface::ATOM),
            'startedAt' => $plan->getStartedAt()?->format(\DateTimeInterface::ATOM),
            'completedAt' => $plan->getCompletedAt()?->format(\DateTimeInterface::ATOM),
            'targetDistance' => $plan->getTargetDistance(),
            'targetDuration' => $plan->getTargetDuration(),
            'rewardTiers' => $plan->getRewardTiers(),
            'exercises' => $exercises,
        ];
    }

    /**
     * Serialize a workout plan summary (without exercises) for list endpoints.
     */
    private function serializePlanSummary(WorkoutPlan $plan): array
    {
        return [
            'id' => $plan->getId()->toRfc4122(),
            'name' => $plan->getName(),
            'status' => $plan->getStatus()->value,
            'activityType' => $plan->getActivityType(),
            'targetMuscleGroups' => $plan->getTargetMuscleGroups(),
            'plannedAt' => $plan->getPlannedAt()->format(\DateTimeInterface::ATOM),
            'targetDistance' => $plan->getTargetDistance(),
            'targetDuration' => $plan->getTargetDuration(),
        ];
    }

    /**
     * Calculate XP reward for a completed plan based on exercise completion and reward tiers.
     *
     * For strength plans: counts exercises with at least one logged set.
     * For cardio plans: would compare actual distance/duration to tier thresholds.
     *
     * Returns the highest tier XP the user achieved.
     */
    private function calculateXpReward(WorkoutPlan $plan): int
    {
        $rewardTiers = $plan->getRewardTiers();
        if ($rewardTiers === null) {
            return 0;
        }

        // Count exercises that have at least one logged set
        $completedExercises = 0;
        $totalExercises = $plan->getExercises()->count();
        $hasExtraSet = false;

        foreach ($plan->getExercises() as $planExercise) {
            $logCount = $planExercise->getLogs()->count();
            if ($logCount > 0) {
                $completedExercises++;

                // Check if user did more sets than prescribed (gold tier)
                if ($logCount > $planExercise->getSets()) {
                    $hasExtraSet = true;
                }
            }
        }

        // Determine which tier was achieved (check from highest to lowest)
        if ($hasExtraSet && $completedExercises >= $totalExercises && isset($rewardTiers['gold'])) {
            return (int) $rewardTiers['gold']['xp'];
        }

        if ($completedExercises >= $totalExercises && $totalExercises > 0 && isset($rewardTiers['silver'])) {
            return (int) $rewardTiers['silver']['xp'];
        }

        if ($completedExercises >= 3 && isset($rewardTiers['bronze'])) {
            return (int) $rewardTiers['bronze']['xp'];
        }

        // Some exercises completed but didn't meet any tier
        if ($completedExercises > 0 && isset($rewardTiers['bronze'])) {
            return (int) ($rewardTiers['bronze']['xp'] / 2);
        }

        return 0;
    }
}
