<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Enum;

/** Character body slot where equipment items can be worn. Each slot accepts exactly one item at a time. */
enum EquipmentSlot: string
{
    case Weapon = 'weapon';
    case Shield = 'shield';
    case Head = 'head';         // helmet
    case Body = 'body';         // chest armor
    case Legs = 'legs';         // pants/greaves
    case Feet = 'feet';         // boots
    case Hands = 'hands';       // gloves
    case Bracers = 'bracers';   // arm guards
    case Bracelet = 'bracelet'; // wrist accessory
    case Ring = 'ring';         // finger ring
    case Shirt = 'shirt';       // undershirt/tunic
    case Necklace = 'necklace'; // neck accessory
}
