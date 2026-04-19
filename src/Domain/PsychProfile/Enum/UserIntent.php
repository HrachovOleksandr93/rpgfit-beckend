<?php

declare(strict_types=1);

namespace App\Domain\PsychProfile\Enum;

/**
 * Daily intent captured in Q3 of the check-in.
 *
 * Domain layer (PsychProfile bounded context). Drives the StatusAssignment
 * decision tree (spec 2026-04-18 §3) together with mood and energy.
 *
 * - REST     -> "Відпочити" / "Rest"
 * - MAINTAIN -> "Утримати ритм" / "Keep rhythm"
 * - PUSH     -> "Натиснути" / "Push"
 */
enum UserIntent: string
{
    case REST = 'REST';
    case MAINTAIN = 'MAINTAIN';
    case PUSH = 'PUSH';
}
