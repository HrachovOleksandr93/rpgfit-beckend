<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\Service;

use App\Domain\PsychProfile\Entity\PhysicalStateAnswer;
use App\Domain\PsychProfile\Entity\PsychCheckIn;
use App\Domain\PsychProfile\Entity\PsychUserProfile;
use App\Domain\PsychProfile\Enum\MoodQuadrant;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\PsychProfile\Enum\UserIntent;
use App\Domain\User\Entity\User;
use App\Infrastructure\PsychProfile\Repository\PsychCheckInRepository;
use App\Infrastructure\PsychProfile\Repository\PsychUserProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Records a daily psych check-in and updates the user's profile.
 *
 * Application layer (PsychProfile bounded context). Contains the only
 * mutation path for `psych_check_ins` and `psych_user_profiles`; other
 * services are read-only. All writes are audited via the `psych` monolog
 * channel (spec 2026-04-18 §5.6).
 *
 * Skip policy (spec §1 decision 8):
 *  - Skip inherits the previous day's status.
 *  - Consecutive skip counter increments on skip, resets to 0 on answered
 *    check-in.
 *  - On the 7th consecutive skip (counter reaches 7) the assigned status
 *    is forced to STEADY, regardless of previous history. The 5% per-skip
 *    decay described in the spec is folded into this deterministic
 *    counter-based rule for beta predictability — revisit post-beta.
 *
 * Psych v2 additions (spec 2026-04-19):
 *  - Accepts an optional session-RPE (`$rpeScore`). When provided, the
 *    Q4 row is either recorded inline OR the most recent Q4 row written
 *    in the 2h merge window is attached to this check-in.
 *  - A "full" check-in (mood + energy + intent + Q4) sets a completion
 *    bonus eligibility marker via CompletionBonusService for today.
 *  - When no workout happened today, Q4 is OPTIONAL — a Q1-Q3 answer
 *    still counts as "full" for the bonus.
 */
final class CheckInService
{
    /** Hard reset threshold (spec §1 decision 8). */
    public const SKIP_RESET_THRESHOLD = 7;

    public function __construct(
        private readonly StatusAssignmentService $statusAssignment,
        private readonly PsychCheckInRepository $checkInRepository,
        private readonly PsychUserProfileRepository $profileRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly ?PhysicalStateService $physicalStateService = null,
        private readonly ?CompletionBonusService $completionBonusService = null,
    ) {
    }

    public function checkIn(
        User $user,
        ?MoodQuadrant $mood,
        ?int $energy,
        ?UserIntent $intent,
        bool $skipped,
        ?int $rpeScore = null,
    ): PsychCheckIn {
        $now = new \DateTimeImmutable();
        $today = $now->setTime(0, 0, 0);

        $profile = $this->profileRepository->findOrCreateForUser($user);

        // Idempotency: a second call on the same local day reuses the existing
        // row (status stays frozen until local midnight per spec §1 decision 7).
        $existing = $this->checkInRepository->findForUserOnDate($user, $today);
        if ($existing !== null) {
            return $existing;
        }

        [$status, $consecutiveSkips] = $this->resolveStatus($profile, $mood, $energy, $intent, $skipped);

        $checkIn = new PsychCheckIn();
        $checkIn->setUser($user)
            ->setMoodQuadrant($skipped ? null : $mood)
            ->setEnergyLevel($skipped ? null : $energy)
            ->setIntent($skipped ? null : $intent)
            ->setAssignedStatus($status)
            ->setSkipped($skipped)
            ->setCheckedInOn($today);

        // Psych v2: attach a Q4 answer — either a fresh row recorded
        // here, or the most recent row in the 2h merge window.
        $q4 = $this->resolveQ4($user, $skipped, $rpeScore);
        if ($q4 !== null) {
            $checkIn->setPhysicalStateAnswer($q4);
        }

        $profile->setCurrentStatus($status)
            ->setStatusValidUntil($today->modify('+1 day'))
            ->setConsecutiveSkips($consecutiveSkips)
            ->setLastCheckInAt($now);

        $this->entityManager->persist($checkIn);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        // Psych v2: mark completion-bonus eligibility when Q1-Q3 are all
        // answered (non-skip). Q4 is optional — spec §1.1 treats Q1-Q3 as
        // sufficient on a non-workout day.
        if (!$skipped && $mood !== null && $energy !== null && $intent !== null) {
            $this->completionBonusService?->markEligibility($user, $today);
        }

        $this->logger->info('psych.check-in', [
            'userId' => $user->getId()->toRfc4122(),
            'status' => $status->value,
            'skipped' => $skipped,
            'consecutiveSkips' => $consecutiveSkips,
            'hasRpe' => $q4 !== null,
        ]);

        return $checkIn;
    }

    /**
     * Decide which Q4 answer (if any) to link to the new check-in.
     *
     * - When $rpeScore is provided AND the check-in is not skipped,
     *   record a fresh PhysicalStateAnswer inline so there is always
     *   exactly one authoritative Q4 for this check-in.
     * - Otherwise, fall back to the most recent Q4 in the 2h window so
     *   a post-workout answer submitted seconds ago is still merged.
     */
    private function resolveQ4(User $user, bool $skipped, ?int $rpeScore): ?PhysicalStateAnswer
    {
        if ($this->physicalStateService === null) {
            return null;
        }

        if (!$skipped && $rpeScore !== null) {
            return $this->physicalStateService->record($user, null, $rpeScore);
        }

        if ($skipped) {
            return null;
        }

        return $this->physicalStateService->getLatestInWindow($user);
    }

    /**
     * Resolve the status + updated skip counter for the given submission.
     *
     * @return array{0: PsychStatus, 1: int}
     */
    private function resolveStatus(
        PsychUserProfile $profile,
        ?MoodQuadrant $mood,
        ?int $energy,
        ?UserIntent $intent,
        bool $skipped,
    ): array {
        if ($skipped) {
            $nextCount = $profile->getConsecutiveSkips() + 1;
            if ($nextCount >= self::SKIP_RESET_THRESHOLD) {
                return [PsychStatus::STEADY, $nextCount];
            }

            // Inherit previous status; if the profile has never been touched,
            // currentStatus defaults to STEADY (see entity constructor).
            return [$profile->getCurrentStatus(), $nextCount];
        }

        // Answered check-in: require all three answers. Partial answers are
        // rejected at the controller via the validator, but we guard here
        // defensively in case a service caller skips validation.
        if ($mood === null || $energy === null || $intent === null) {
            return [$profile->getCurrentStatus(), 0];
        }

        return [$this->statusAssignment->assign($mood, $energy, $intent), 0];
    }
}
