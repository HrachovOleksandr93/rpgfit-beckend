<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\Service;

use App\Application\PsychProfile\DTO\WorkoutPlanAdaptation;
use App\Domain\PsychProfile\Enum\PsychStatus;
use App\Domain\User\Entity\User;
use App\Infrastructure\Config\Repository\GameSettingRepository;
use App\Infrastructure\PsychProfile\Repository\PsychCheckInRepository;
use App\Infrastructure\PsychProfile\Repository\PsychUserProfileRepository;

/**
 * Computes workout-difficulty deltas from the user's current psych status
 * and latest Q4 (session-RPE) answer (spec §1.3 + §2.2).
 *
 * Application layer (PsychProfile bounded context). Asymmetric contract:
 * can LOWER load for DORMANT/WEARY/SCATTERED or high Q4, can RAISE load
 * ONLY when status=CHARGED AND last Q4 ≤ 2 AND no WEARY/SCATTERED in the
 * last 3 days (spec §1.3 "asymmetric rule").
 *
 * The matrix JSON lives in `game_settings.psych.adapter_matrix`; when the
 * setting is absent the service falls back to the hardcoded §1.3 table
 * so dev environments stay usable before seed migrations run.
 *
 * Matrix shape: each row is
 *   {
 *     "status": "CHARGED|STEADY|DORMANT|WEARY|SCATTERED",
 *     "rpeMin": 1,         // inclusive; optional (no lower bound)
 *     "rpeMax": 2,         // inclusive; optional (no upper bound)
 *     "intensity": 0.10,   // ratio delta
 *     "volume": 0.10,
 *     "duration": 0.0,
 *     "focus": "new-challenge",
 *     "warning": null
 *   }
 *
 * Rows are evaluated in order — first match wins. A row with neither
 * rpeMin nor rpeMax matches any Q4 (including "no Q4 on record").
 *
 * NOT `final` — tests mock this service when integrating with
 * WorkoutPlanGeneratorService.
 */
class PsychWorkoutAdapterService
{
    public const SETTING_KEY = 'psych.adapter_matrix';

    /** Days of recent history scanned for WEARY/SCATTERED presence. */
    private const RECENT_WINDOW_DAYS = 3;

    public function __construct(
        private readonly PsychUserProfileRepository $profileRepository,
        private readonly PsychCheckInRepository $checkInRepository,
        private readonly PhysicalStateService $physicalStateService,
        private readonly GameSettingRepository $gameSettingRepository,
    ) {
    }

    /**
     * Compute the adaptation for the user's next workout plan.
     *
     * Returns baseline (zero deltas, no warning) when:
     *  - user has no psych profile yet;
     *  - user is opted out;
     *  - no matching row exists in the matrix.
     */
    public function adapt(User $user): WorkoutPlanAdaptation
    {
        $profile = $this->profileRepository->findByUser($user);
        if ($profile === null || !$profile->isFeatureOptedIn()) {
            return WorkoutPlanAdaptation::baseline();
        }

        $status = $profile->getCurrentStatus();
        $latestAnswer = $this->physicalStateService->getLatest($user);
        $rpe = $latestAnswer?->getRpeScore();

        $matrix = $this->loadMatrix();
        $row = $this->selectRow($matrix, $status->value, $rpe);
        if ($row === null) {
            return WorkoutPlanAdaptation::baseline();
        }

        // Asymmetric gate — only CHARGED rows with positive deltas can
        // raise load, and only when predicates pass. If blocked, fall
        // through to the next matching row (typically the status's
        // baseline or reduction row).
        if ($this->rowRaisesLoad($row) && !$this->asymmetricRaiseAllowed($user, $rpe)) {
            $fallback = $this->selectRowSkipping($matrix, $status->value, $rpe, $row);
            $row = $fallback ?? [
                'intensity' => 0.0,
                'volume' => 0.0,
                'duration' => 0.0,
                'focus' => $row['focus'] ?? null,
                'warning' => null,
            ];
        }

        return new WorkoutPlanAdaptation(
            intensityDelta: (float) ($row['intensity'] ?? 0.0),
            volumeDelta: (float) ($row['volume'] ?? 0.0),
            durationDelta: (float) ($row['duration'] ?? 0.0),
            focus: isset($row['focus']) ? (string) $row['focus'] : null,
            warningCopy: isset($row['warning']) && $row['warning'] !== ''
                ? (string) $row['warning']
                : null,
        );
    }

    /**
     * Asymmetric predicate from spec §1.3 — to raise load we need:
     *  - user status CHARGED (implicit — caller only invokes for a
     *    matrix row that raises load, and only CHARGED rows in the
     *    hardcoded matrix have positive deltas);
     *  - last Q4 ≤ 2 (if present);
     *  - no WEARY or SCATTERED check-ins in the last 3 days.
     */
    public function asymmetricRaiseAllowed(User $user, ?int $rpe): bool
    {
        if ($rpe !== null && $rpe > 2) {
            return false;
        }

        $today = (new \DateTimeImmutable())->setTime(0, 0, 0);
        $start = $today->modify(sprintf('-%d day', self::RECENT_WINDOW_DAYS));

        $rows = $this->checkInRepository->findInRange($user, $start, $today);
        foreach ($rows as $row) {
            $status = $row->getAssignedStatus();
            if ($status === PsychStatus::WEARY || $status === PsychStatus::SCATTERED) {
                return false;
            }
        }

        return true;
    }

    /** True when the row has any positive delta (intensity/volume/duration). */
    private function rowRaisesLoad(array $row): bool
    {
        return ((float) ($row['intensity'] ?? 0.0)) > 0.0
            || ((float) ($row['volume'] ?? 0.0)) > 0.0
            || ((float) ($row['duration'] ?? 0.0)) > 0.0;
    }

    /**
     * First row whose status matches AND whose rpe range includes $rpe
     * (or which has no range constraint when $rpe is null).
     *
     * @param list<array<string, mixed>> $matrix
     * @return array<string, mixed>|null
     */
    private function selectRow(array $matrix, string $statusValue, ?int $rpe): ?array
    {
        foreach ($matrix as $row) {
            if ($this->rowMatches($row, $statusValue, $rpe)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Like selectRow, but skips the row identified by reference equality
     * so the caller can fall through to the next matching row.
     *
     * @param list<array<string, mixed>> $matrix
     * @param array<string, mixed> $skip
     * @return array<string, mixed>|null
     */
    private function selectRowSkipping(array $matrix, string $statusValue, ?int $rpe, array $skip): ?array
    {
        $found = false;
        foreach ($matrix as $row) {
            if (!$found && $row === $skip) {
                $found = true;
                continue;
            }
            if ($this->rowMatches($row, $statusValue, $rpe)) {
                return $row;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $row */
    private function rowMatches(array $row, string $statusValue, ?int $rpe): bool
    {
        if (($row['status'] ?? null) !== $statusValue) {
            return false;
        }

        if (isset($row['rpeMin']) && $rpe !== null && $rpe < (int) $row['rpeMin']) {
            return false;
        }
        if (isset($row['rpeMax']) && $rpe !== null && $rpe > (int) $row['rpeMax']) {
            return false;
        }

        // If the row declares a range and rpe is null, treat "no Q4" as
        // not-in-range only when the row's rpeMin is > 1 or rpeMax < 5;
        // for WEARY/SCATTERED/DORMANT/STEADY rows without range bounds
        // this check is skipped and the row matches.
        if ($rpe === null && (isset($row['rpeMin']) || isset($row['rpeMax']))) {
            // A constrained row that *requires* a Q4 shouldn't match when
            // Q4 is absent — fall through to the next row (typically the
            // status's unconstrained baseline).
            return false;
        }

        return true;
    }

    /** @return list<array<string, mixed>> */
    private function loadMatrix(): array
    {
        $setting = $this->gameSettingRepository->findByKey(self::SETTING_KEY);
        if ($setting !== null) {
            $decoded = json_decode($setting->getValue(), true);
            if (is_array($decoded) && $decoded !== []) {
                /** @var list<array<string, mixed>> $decoded */
                return array_values(array_filter($decoded, static fn($r): bool => is_array($r)));
            }
        }

        return self::fallbackMatrix();
    }

    /**
     * Hardcoded fallback mirroring spec §1.3 — used only when the
     * `psych.adapter_matrix` setting is absent / unreadable. Order matters
     * (first-match-wins): higher-priority rows come first.
     *
     * @return list<array<string, mixed>>
     */
    public static function fallbackMatrix(): array
    {
        return [
            // CHARGED + fresh (Q4 1-2) — raise load. Asymmetric gate applies.
            [
                'status' => PsychStatus::CHARGED->value,
                'rpeMin' => 1,
                'rpeMax' => 2,
                'intensity' => 0.10,
                'volume' => 0.10,
                'duration' => 0.0,
                'focus' => 'new-challenge',
                'warning' => null,
            ],
            // CHARGED — any other Q4 or no Q4: baseline, new-challenge focus.
            [
                'status' => PsychStatus::CHARGED->value,
                'intensity' => 0.0,
                'volume' => 0.0,
                'duration' => 0.0,
                'focus' => 'new-challenge',
                'warning' => null,
            ],
            // STEADY — baseline.
            [
                'status' => PsychStatus::STEADY->value,
                'intensity' => 0.0,
                'volume' => 0.0,
                'duration' => 0.0,
                'focus' => 'baseline',
                'warning' => null,
            ],
            // DORMANT — reduce load, mobility focus.
            [
                'status' => PsychStatus::DORMANT->value,
                'intensity' => -0.20,
                'volume' => -0.15,
                'duration' => -0.20,
                'focus' => 'mobility',
                'warning' => 'Ти відпочиваєш. Vector рекомендує легке.',
            ],
            // WEARY + tired (Q4 4-5) — deep reduction. Evaluated BEFORE
            // the WEARY 1-3 row so the tired bucket wins when applicable.
            [
                'status' => PsychStatus::WEARY->value,
                'rpeMin' => 4,
                'rpeMax' => 5,
                'intensity' => -0.35,
                'volume' => -0.35,
                'duration' => -0.30,
                'focus' => 'recovery-only',
                'warning' => 'Сильна втома. Рекомендуємо лише легке розслаблення.',
            ],
            // WEARY — otherwise (Q4 1-3 or no Q4). Moderate reduction.
            [
                'status' => PsychStatus::WEARY->value,
                'intensity' => -0.25,
                'volume' => -0.25,
                'duration' => -0.20,
                'focus' => 'recovery',
                'warning' => 'Ми фіксуємо що ти стомлений. Бережи себе.',
            ],
            // SCATTERED — dial back, focus / breath.
            [
                'status' => PsychStatus::SCATTERED->value,
                'intensity' => -0.15,
                'volume' => -0.15,
                'duration' => -0.10,
                'focus' => 'focus',
                'warning' => 'Сигнал рваний. Зосередься на дихальних вправах.',
            ],
        ];
    }
}
