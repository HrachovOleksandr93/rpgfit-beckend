<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

/**
 * User gender, collected during the onboarding questionnaire.
 *
 * Used for personalizing fitness recommendations and RPG character appearance.
 */
enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
}
