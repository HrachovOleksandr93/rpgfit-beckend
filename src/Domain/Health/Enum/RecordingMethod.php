<?php

declare(strict_types=1);

namespace App\Domain\Health\Enum;

enum RecordingMethod: string
{
    case Automatic = 'automatic';
    case Manual = 'manual';
}
