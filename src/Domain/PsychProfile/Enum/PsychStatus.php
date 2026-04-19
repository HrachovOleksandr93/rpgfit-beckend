<?php

declare(strict_types=1);

namespace App\Domain\PsychProfile\Enum;

/**
 * Derived psych status assigned by StatusAssignmentService.
 *
 * Domain layer (PsychProfile bounded context). Vector-dialect badge copy
 * (spec 2026-04-18 §3) lives alongside the enum case for convenience;
 * localisation layers are free to override via i18n tables later.
 *
 * Red-line (spec §4): status never penalises damage. XP multiplier is
 * activity-contextual and capped at +-15%.
 */
enum PsychStatus: string
{
    case CHARGED = 'CHARGED';
    case STEADY = 'STEADY';
    case DORMANT = 'DORMANT';
    case WEARY = 'WEARY';
    case SCATTERED = 'SCATTERED';

    /** UA badge copy (Vector-dialect). */
    public function badgeCopyUa(): string
    {
        return match ($this) {
            self::CHARGED => 'Іскра є. Спали її.',
            self::STEADY => 'Сигнал чистий. Тримай лінію.',
            self::DORMANT => 'Ядро спить. Не тисни.',
            self::WEARY => 'Батарея на нулі. Іди, не біжи.',
            self::SCATTERED => 'Пульс рваний. Один подих за раз.',
        };
    }

    /** EN badge copy (Vector-dialect). */
    public function badgeCopyEn(): string
    {
        return match ($this) {
            self::CHARGED => "Spark is on. Burn it.",
            self::STEADY => "Signal's clean. Hold line.",
            self::DORMANT => "The core is resting. Don't force it.",
            self::WEARY => "Battery's low. Walk, don't run.",
            self::SCATTERED => "Pulse is jagged. One breath at a time.",
        };
    }
}
