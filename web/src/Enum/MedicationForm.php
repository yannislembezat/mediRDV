<?php

declare(strict_types=1);

namespace App\Enum;

enum MedicationForm: string
{
    use BackedEnumOptionsTrait;

    case COMPRIME = 'comprimé';
    case GELULE = 'gélule';
    case SIROP = 'sirop';
    case INJECTION = 'injection';
    case POMMADE = 'pommade';
    case GOUTTES = 'gouttes';
    case SUPPOSITOIRE = 'suppositoire';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::COMPRIME => 'Comprimé',
            self::GELULE => 'Gélule',
            self::SIROP => 'Sirop',
            self::INJECTION => 'Injection',
            self::POMMADE => 'Pommade',
            self::GOUTTES => 'Gouttes',
            self::SUPPOSITOIRE => 'Suppositoire',
            self::AUTRE => 'Autre',
        };
    }
}
