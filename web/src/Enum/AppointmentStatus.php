<?php

namespace App\Enum;

enum AppointmentStatus: string
{
    use BackedEnumOptionsTrait;

    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case REFUSED = 'refused';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::CONFIRMED => 'Confirmé',
            self::REFUSED => 'Refusé',
            self::COMPLETED => 'Complété',
            self::CANCELLED => 'Annulé',
        };
    }
}
