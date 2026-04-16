<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Appointment;
use App\Enum\AppointmentStatus;

final readonly class AppointmentStatusChangedEvent
{
    public function __construct(
        private Appointment $appointment,
        private AppointmentStatus $previousStatus,
        private AppointmentStatus $currentStatus,
    ) {
    }

    public function getAppointment(): Appointment
    {
        return $this->appointment;
    }

    public function getPreviousStatus(): AppointmentStatus
    {
        return $this->previousStatus;
    }

    public function getCurrentStatus(): AppointmentStatus
    {
        return $this->currentStatus;
    }
}
