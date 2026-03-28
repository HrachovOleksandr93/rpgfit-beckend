<?php

declare(strict_types=1);

namespace App\Domain\User\Enum;

enum CharacterRace: string
{
    case Human = 'human';
    case Orc = 'orc';
    case Dwarf = 'dwarf';
    case DarkElf = 'dark_elf';
    case LightElf = 'light_elf';
}
