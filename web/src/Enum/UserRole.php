<?php

namespace App\Enum;

enum UserRole: string
{
    use BackedEnumOptionsTrait;

    case PATIENT = 'ROLE_PATIENT';
    case ADMIN = 'ROLE_ADMIN';
    case MEDECIN = 'ROLE_MEDECIN';

    public function label(): string
    {
        return match ($this) {
            self::PATIENT => 'Patient',
            self::ADMIN => 'Administrateur',
            self::MEDECIN => 'Médecin',
        };
    }
}
