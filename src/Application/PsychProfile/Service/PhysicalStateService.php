<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\Service;

use App\Domain\Battle\Entity\WorkoutSession;
use App\Domain\PsychProfile\Entity\PhysicalStateAnswer;
use App\Domain\User\Entity\User;
use App\Infrastructure\PsychProfile\Repository\PhysicalStateAnswerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Records Q4 / session-RPE answers and exposes recent-window lookups
 * for the daily check-in merge (Psych v2 spec §1.2 + §2.2).
 *
 * Application layer (PsychProfile bounded context). Q4 is purely about
 * physical state — it does NOT mutate PsychStatus. Status stays under
 * StatusAssignmentService's control (unchanged from v1).
 */
final class PhysicalStateService
{
    /** Merge window for linking Q4 to a daily check-in (spec §1.2). */
    public const MERGE_WINDOW_HOURS = 2;

    public function __construct(
        private readonly PhysicalStateAnswerRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Persist a new Q4 answer for the user.
     *
     * @param int $rpe 1..5 — clamped inside the entity setter.
     */
    public function record(User $user, ?WorkoutSession $session, int $rpe): PhysicalStateAnswer
    {
        $answer = new PhysicalStateAnswer();
        $answer->setUser($user)
            ->setWorkoutSession($session)
            ->setRpeScore($rpe);

        $this->entityManager->persist($answer);
        $this->entityManager->flush();

        $this->logger->info('psych.physical-state', [
            'userId' => $user->getId()->toRfc4122(),
            'rpe' => $answer->getRpeScore(),
            'sessionId' => $session?->getId()->toRfc4122(),
        ]);

        return $answer;
    }

    /**
     * Most recent Q4 answer for the user within the 2h window — used by
     * CheckInService to link the answer to the daily check-in row.
     */
    public function getLatestInWindow(User $user, int $hours = self::MERGE_WINDOW_HOURS): ?PhysicalStateAnswer
    {
        return $this->repository->findLatestForUserWithin($user, $hours);
    }

    /** Most recent Q4 answer regardless of age — used by PsychWorkoutAdapterService. */
    public function getLatest(User $user): ?PhysicalStateAnswer
    {
        return $this->repository->findLatestForUser($user);
    }
}
