<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Battle\Service\BattleService;
use App\Domain\Battle\Enum\BattleMode;
use App\Domain\User\Entity\User;
use App\Domain\Workout\Entity\Exercise;
use App\Infrastructure\Battle\Repository\WorkoutSessionRepository;
use App\Infrastructure\Workout\Repository\ExerciseRepository;
use App\Infrastructure\Workout\Repository\WorkoutPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * API controller for the battle session system.
 *
 * Provides endpoints for starting, completing, abandoning battle sessions,
 * querying active sessions, and listing available exercises for custom mode.
 * All endpoints require JWT authentication.
 *
 * Endpoints:
 * - POST /api/battle/start     Start a new battle session
 * - GET  /api/battle/active    Get the user's current active session
 * - POST /api/battle/complete  Submit exercise results and complete a session
 * - POST /api/battle/abandon   Abandon an active session
 * - GET  /api/exercises        List exercises grouped by muscle group
 */
class BattleController extends AbstractController
{
    public function __construct(
        private readonly BattleService $battleService,
        private readonly WorkoutSessionRepository $sessionRepository,
        private readonly WorkoutPlanRepository $workoutPlanRepository,
        private readonly ExerciseRepository $exerciseRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Start a new battle session with a workout plan and battle mode.
     *
     * Selects an appropriate mob, creates a WorkoutSession, and returns
     * the session data including mob stats (adjusted for raid mode).
     */
    #[Route('/api/battle/start', name: 'api_battle_start', methods: ['POST'])]
    public function start(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['workoutPlanId'], $data['mode'])) {
            return $this->json(
                ['error' => 'workoutPlanId and mode are required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $plan = $this->workoutPlanRepository->find($data['workoutPlanId']);
        if ($plan === null) {
            return $this->json(['error' => 'Workout plan not found.'], Response::HTTP_NOT_FOUND);
        }

        // Verify plan ownership
        if ($plan->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        $mode = BattleMode::tryFrom($data['mode']);
        if ($mode === null) {
            return $this->json(
                ['error' => 'Invalid mode. Must be: custom, recommended, or raid.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $session = $this->battleService->startBattle($user, $plan, $mode);

        return $this->json([
            'sessionId' => $session->getId()->toRfc4122(),
            'mode' => $session->getMode()->value,
            'mob' => $this->serializeMob($session),
            'startedAt' => $session->getStartedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    /**
     * Get the user's currently active battle session.
     *
     * Returns 404 if no active session exists.
     */
    #[Route('/api/battle/active', name: 'api_battle_active', methods: ['GET'])]
    public function active(#[CurrentUser] User $user): JsonResponse
    {
        $session = $this->sessionRepository->findActiveByUser($user);

        if ($session === null) {
            return $this->json(['error' => 'No active battle session.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'sessionId' => $session->getId()->toRfc4122(),
            'mode' => $session->getMode()->value,
            'mob' => $this->serializeMob($session),
            'startedAt' => $session->getStartedAt()->format(\DateTimeInterface::ATOM),
            'status' => $session->getStatus()->value,
        ]);
    }

    /**
     * Complete a battle session by submitting exercise results and health data.
     *
     * Processes all exercise sets, calculates damage, determines if the mob
     * was defeated, awards XP, and returns the battle results.
     */
    #[Route('/api/battle/complete', name: 'api_battle_complete', methods: ['POST'])]
    public function complete(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['sessionId'])) {
            return $this->json(
                ['error' => 'sessionId is required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $session = $this->sessionRepository->findById($data['sessionId']);
        if ($session === null) {
            return $this->json(['error' => 'Session not found.'], Response::HTTP_NOT_FOUND);
        }

        // Verify session ownership
        if ($session->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        // Verify session is active
        if ($session->getStatus() !== \App\Domain\Battle\Enum\SessionStatus::Active) {
            return $this->json(
                ['error' => 'Session is not active.'],
                Response::HTTP_CONFLICT,
            );
        }

        $exercises = $data['exercises'] ?? [];
        $healthData = $data['healthData'] ?? null;

        $result = $this->battleService->completeBattle($session, $exercises, $healthData);

        return $this->json([
            'xpAwarded' => $result['xpAwarded'],
            'mobDefeated' => $result['mobDefeated'],
            'damageDealt' => $result['damageDealt'],
            'rewardTier' => $result['rewardTier'],
            'levelUp' => $result['levelUp'],
            'newLevel' => $result['newLevel'],
            'totalXp' => $result['totalXp'],
            'session' => $this->serializeSession($result['session']),
        ]);
    }

    /**
     * Abandon an active battle session without completing it.
     *
     * Sets the session to abandoned and the associated plan to skipped.
     */
    #[Route('/api/battle/abandon', name: 'api_battle_abandon', methods: ['POST'])]
    public function abandon(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['sessionId'])) {
            return $this->json(
                ['error' => 'sessionId is required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $session = $this->sessionRepository->findById($data['sessionId']);
        if ($session === null) {
            return $this->json(['error' => 'Session not found.'], Response::HTTP_NOT_FOUND);
        }

        // Verify session ownership
        if ($session->getUser()->getId()->toRfc4122() !== $user->getId()->toRfc4122()) {
            return $this->json(['error' => 'Access denied.'], Response::HTTP_FORBIDDEN);
        }

        if ($session->getStatus() !== \App\Domain\Battle\Enum\SessionStatus::Active) {
            return $this->json(
                ['error' => 'Session is not active.'],
                Response::HTTP_CONFLICT,
            );
        }

        $this->battleService->abandonBattle($session);

        return $this->json(['status' => 'abandoned']);
    }

    /**
     * List available exercises grouped by muscle group for custom battle mode.
     *
     * Supports filtering by activityCategory, muscleGroup, search text, and difficulty.
     */
    #[Route('/api/exercises', name: 'api_exercises_list', methods: ['GET'])]
    public function listExercises(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('e')
            ->from(Exercise::class, 'e')
            ->orderBy('e.primaryMuscle', 'ASC')
            ->addOrderBy('e.priority', 'ASC');

        // Filter by activity category
        $activityCategory = $request->query->get('activityCategory');
        if ($activityCategory !== null && $activityCategory !== '') {
            $qb->andWhere('e.activityCategory = :activityCategory')
                ->setParameter('activityCategory', $activityCategory);
        }

        // Filter by muscle group
        $muscleGroup = $request->query->get('muscleGroup');
        if ($muscleGroup !== null && $muscleGroup !== '') {
            $qb->andWhere('e.primaryMuscle = :muscleGroup')
                ->setParameter('muscleGroup', $muscleGroup);
        }

        // Search by name
        $search = $request->query->get('search');
        if ($search !== null && $search !== '') {
            $qb->andWhere('e.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Filter by difficulty
        $difficulty = $request->query->get('difficulty');
        if ($difficulty !== null && $difficulty !== '') {
            $qb->andWhere('e.difficulty = :difficulty')
                ->setParameter('difficulty', $difficulty);
        }

        $exercises = $qb->getQuery()->getResult();

        // Group exercises by primary muscle group
        $groups = [];
        foreach ($exercises as $exercise) {
            $muscle = $exercise->getPrimaryMuscle()->value;
            $groups[$muscle][] = [
                'slug' => $exercise->getSlug(),
                'name' => $exercise->getName(),
                'equipment' => $exercise->getEquipment()->value,
                'difficulty' => $exercise->getDifficulty()->value,
                'priority' => $exercise->getPriority(),
            ];
        }

        return $this->json(['groups' => $groups]);
    }

    // ========================================================================
    // Serialization Helpers
    // ========================================================================

    /** Serialize mob data from a workout session for API response. */
    private function serializeMob(\App\Domain\Battle\Entity\WorkoutSession $session): ?array
    {
        $mob = $session->getMob();
        if ($mob === null) {
            return null;
        }

        return [
            'id' => $mob->getId()->toRfc4122(),
            'name' => $mob->getName(),
            'level' => $mob->getLevel(),
            'hp' => $session->getMobHp(),
            'xpReward' => $session->getMobXpReward(),
            'rarity' => $mob->getRarity()?->value,
            'image' => $mob->getImage()?->getPublicUrl(),
        ];
    }

    /** Serialize a full workout session for API response. */
    private function serializeSession(\App\Domain\Battle\Entity\WorkoutSession $session): array
    {
        return [
            'id' => $session->getId()->toRfc4122(),
            'mode' => $session->getMode()->value,
            'status' => $session->getStatus()->value,
            'mobHp' => $session->getMobHp(),
            'mobXpReward' => $session->getMobXpReward(),
            'totalDamageDealt' => $session->getTotalDamageDealt(),
            'xpAwarded' => $session->getXpAwarded(),
            'startedAt' => $session->getStartedAt()->format(\DateTimeInterface::ATOM),
            'completedAt' => $session->getCompletedAt()?->format(\DateTimeInterface::ATOM),
            'healthData' => $session->getHealthData(),
        ];
    }
}
