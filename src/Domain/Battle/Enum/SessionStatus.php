<?php

declare(strict_types=1);

namespace App\Domain\Battle\Enum;

/** Lifecycle status of a workout battle session. */
enum SessionStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
}
