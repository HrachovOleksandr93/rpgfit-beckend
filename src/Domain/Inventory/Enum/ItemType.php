<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Enum;

/** Categorizes items in the game inventory: wearable equipment, scrolls for skill unlocks, and consumable potions. */
enum ItemType: string
{
    case Equipment = 'equipment';
    case Scroll = 'scroll';
    case Potion = 'potion';
}
