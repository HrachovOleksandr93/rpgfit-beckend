<?php

declare(strict_types=1);

namespace App\Domain\Health\Enum;

/** How a health data point was recorded: automatically by device sensors or manually entered by the user. */
enum RecordingMethod: string
{
    case Automatic = 'automatic';
    case Manual = 'manual';
}
