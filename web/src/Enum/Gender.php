<?php

namespace App\Enum;

enum Gender: string
{
    use BackedEnumOptionsTrait;

    case MALE = 'M';
    case FEMALE = 'F';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Masculin',
            self::FEMALE => 'Féminin',
        };
    }
}
