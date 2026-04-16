<?php

namespace App\Enum;

enum NotificationType: string
{
    use BackedEnumOptionsTrait;

    case APPOINTMENT_CONFIRMED = 'appointment_confirmed';
    case APPOINTMENT_REFUSED = 'appointment_refused';
    case APPOINTMENT_REMINDER = 'appointment_reminder';
    case CONSULTATION_READY = 'consultation_ready';

    public function label(): string
    {
        return match ($this) {
            self::APPOINTMENT_CONFIRMED => 'Rendez-vous confirmé',
            self::APPOINTMENT_REFUSED => 'Rendez-vous refusé',
            self::APPOINTMENT_REMINDER => 'Rappel de rendez-vous',
            self::CONSULTATION_READY => 'Consultation disponible',
        };
    }
}
