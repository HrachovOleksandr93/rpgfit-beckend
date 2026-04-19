<?php

declare(strict_types=1);

namespace App\Application\Test\Service;

use App\Application\Battle\DTO\BattleResult;
use App\Application\Battle\Service\BattleResultCalculator;
use App\Application\Battle\Service\BattleService;
use App\Domain\Battle\Entity\WorkoutSession;
use App\Domain\Battle\Enum\BattleMode;
use App\Domain\Battle\Enum\SessionStatus;
use App\Domain\User\Entity\User;
use App\Domain\Workout\Entity\WorkoutPlan;
use App\Domain\Workout\Enum\WorkoutPlanStatus;
use App\Infrastructure\Mob\Repository\MobRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * End-to-end battle simulation for the harness.
 *
 * Creates an ephemeral `WorkoutPlan` + `WorkoutSession`, then hands the
 * session to the real `BattleResultCalculator` — the scoring engine code
 * path is exactly the same as production; only the setup is synthetic.
 */
final class BattleTestService
{
    public function __construct(
        private readonly BattleService $battleService,
        private readonly BattleResultCalculator $battleResultCalculator,
        private readonly MobRepository $mobRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     *
     * Options:
     *  - mobSlug        string|null  pick a specific mob (else auto-selected)
     *  - mode           string       battle mode (`custom`, `recommended`, `raid`)
     *  - damageMultiplier float      scales the synthetic training volume
     *  - performanceTier string|null clamp outcome (not yet wired into calc)
     */
    public function simulate(User $user, array $options): BattleResult
    {
        $mode = BattleMode::tryFrom((string) ($options['mode'] ?? BattleMode::Recommended->value))
            ?? BattleMode::Recommended;
        $damageMultiplier = (float) ($options['damageMultiplier'] ?? 1.0);
        $damageMultiplier = max(0.1, min(10.0, $damageMultiplier));

        $plan = $this->buildSyntheticPlan($user);

        // We bypass BattleService::startBattle() on purpose: it would also
        // select a mob, but we want to respect `mobSlug` when present.
        $session = new WorkoutSession();
        $session->setUser($user);
        $session->setWorkoutPlan($plan);
        $session->setMode($mode);

        if (isset($options['mobSlug']) && is_string($options['mobSlug']) && $options['mobSlug'] !== '') {
            $mob = $this->mobRepository->findBySlug($options['mobSlug']);
            if ($mob !== null) {
                $session->setMob($mob);
                $session->setMobHp($mob->getHp());
                $session->setMobXpReward($mob->getXpReward());
            }
        }

        // If no mob was bound yet, delegate to BattleMobService via the
        // production flow (startBattle would abandon an existing session for us).
        if ($session->getMob() === null) {
            $session = $this->battleService->startBattle($user, $plan, $mode);
        } else {
            $this->entityManager->persist($session);
            $this->entityManager->flush();
        }

        // Build plausible exercise + health data so the calculator produces
        // a non-zero score; the `$damageMultiplier` scales the effective volume.
        $exercises = [
            [
                'exerciseSlug' => 'synthetic',
                'sets' => [
                    ['setNumber' => 1, 'reps' => 10, 'weight' => 50 * $damageMultiplier, 'duration' => 0],
                ],
            ],
        ];
        $healthData = [
            'duration' => (int) round(600 * $damageMultiplier),
            'calories' => (int) round(250 * $damageMultiplier),
        ];

        $result = $this->battleResultCalculator->calculateBattleResult(
            $session,
            $exercises,
            $healthData,
            usedSkillSlugs: [],
            usedConsumableSlugs: [],
        );

        $session->setStatus(SessionStatus::Completed);
        $session->setCompletedAt(new \DateTimeImmutable());
        $plan->setStatus(WorkoutPlanStatus::Completed);
        $plan->setCompletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $result;
    }

    /**
     * Abandon any currently-active session for the user. Idempotent.
     */
    public function abandonActive(User $user): bool
    {
        $repo = $this->entityManager->getRepository(WorkoutSession::class);
        $active = $repo->findOneBy(['user' => $user, 'status' => SessionStatus::Active]);
        if ($active === null) {
            return false;
        }

        $this->battleService->abandonBattle($active);

        return true;
    }

    private function buildSyntheticPlan(User $user): WorkoutPlan
    {
        $plan = new WorkoutPlan();
        $plan->setUser($user);
        $plan->setName('Test harness synthetic plan');
        $plan->setStatus(WorkoutPlanStatus::Pending);
        $plan->setPlannedAt(new \DateTimeImmutable());
        $plan->setActivityType('strength_training');
        $plan->setTargetDuration(30);

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        return $plan;
    }
}
