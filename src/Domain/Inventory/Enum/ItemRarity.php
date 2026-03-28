<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Enum;

/** Item rarity tier, from most common to most rare. Affects drop rates and stat bonus magnitude. */
enum ItemRarity: string
{
    case Common = 'common';
    case Uncommon = 'uncommon';
    case Rare = 'rare';
    case Epic = 'epic';
    case Legendary = 'legendary';
}
