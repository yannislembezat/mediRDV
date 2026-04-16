<?php

declare(strict_types=1);

namespace App\Service\Appointment;

use App\Enum\AppointmentStatus;
use App\Service\Exception\InvalidAppointmentStatusTransitionException;

final class AppointmentStatusTransitionGuard
{
    /**
     * @var array<string, list<AppointmentStatus>>
     */
    private const ALLOWED_TRANSITIONS = [
        AppointmentStatus::PENDING->value => [
            AppointmentStatus::CONFIRMED,
            AppointmentStatus::REFUSED,
            AppointmentStatus::CANCELLED,
        ],
        AppointmentStatus::CONFIRMED->value => [
            AppointmentStatus::COMPLETED,
        ],
        AppointmentStatus::REFUSED->value => [],
        AppointmentStatus::COMPLETED->value => [],
        AppointmentStatus::CANCELLED->value => [],
    ];

    public function assertCanTransition(AppointmentStatus $from, AppointmentStatus $to): void
    {
        if (!$this->canTransition($from, $to)) {
            throw InvalidAppointmentStatusTransitionException::fromStatuses($from, $to);
        }
    }

    public function canTransition(AppointmentStatus $from, AppointmentStatus $to): bool
    {
        return in_array($to, self::ALLOWED_TRANSITIONS[$from->value] ?? [], true);
    }

    /**
     * @return list<AppointmentStatus>
     */
    public function allowedTargetsFor(AppointmentStatus $from): array
    {
        return self::ALLOWED_TRANSITIONS[$from->value] ?? [];
    }
}
