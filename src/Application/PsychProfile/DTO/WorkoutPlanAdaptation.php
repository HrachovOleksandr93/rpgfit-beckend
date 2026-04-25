<?php

declare(strict_types=1);

namespace App\Application\PsychProfile\DTO;

/**
 * Immutable value object returned by PsychWorkoutAdapterService (spec §1.3).
 *
 * Carries the multiplicative deltas (intensity / volume / duration) to
 * apply against a baseline workout plan, plus the focus hint and an
 * optional warning copy to surface in the client modal (spec §1.4).
 *
 * All deltas are expressed as ratios centred on 1.0 — e.g. -0.20 = 20%
 * reduction, +0.10 = 10% increase. A "baseline" row in the matrix
 * translates to 0.0 deltas.
 */
final class WorkoutPlanAdaptation
{
    public function __construct(
        public readonly float $intensityDelta,
        public readonly float $volumeDelta,
        public readonly float $durationDelta,
        public readonly ?string $focus,
        public readonly ?string $warningCopy,
    ) {
    }

    /** Baseline / no-op adaptation (feature off, no profile, STEADY status). */
    public static function baseline(): self
    {
        return new self(0.0, 0.0, 0.0, null, null);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'intensityDelta' => $this->intensityDelta,
            'volumeDelta' => $this->volumeDelta,
            'durationDelta' => $this->durationDelta,
            'focus' => $this->focus,
            'warningCopy' => $this->warningCopy,
        ];
    }
}
