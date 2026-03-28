<?php

declare(strict_types=1);

namespace App\Domain\Health\Enum;

/** Mobile platform that the health data originated from. Sent with each sync request. */
enum Platform: string
{
    case Ios = 'ios';
    case Android = 'android';
}
