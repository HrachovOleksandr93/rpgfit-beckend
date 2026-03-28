<?php

declare(strict_types=1);

namespace App\Domain\Health\Enum;

enum Platform: string
{
    case Ios = 'ios';
    case Android = 'android';
}
