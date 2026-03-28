<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Enum;

/** Character body slot where equipment items can be worn. Each slot accepts exactly one item at a time. */
enum EquipmentSlot: string
{
    case Head = 'head';
    case Body = 'body';
    case Legs = 'legs';
    case Feet = 'feet';
    case Hands = 'hands';
    case Weapon = 'weapon';
    case Shield = 'shield';
    case Accessory = 'accessory';
}
