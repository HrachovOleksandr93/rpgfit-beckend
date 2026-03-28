<?php

declare(strict_types=1);

namespace App\Domain\Workout\Enum;

/** Training split strategy that determines how muscle groups are distributed across days. */
enum SplitType: string
{
    case FullBody = 'full_body';
    case PushPullLegs = 'push_pull_legs';
    case UpperLower = 'upper_lower';
    case BroSplit = 'bro_split';
    case Custom = 'custom';
}
