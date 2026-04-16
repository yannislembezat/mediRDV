<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Enum\AppointmentStatus;

final class InvalidAppointmentStatusTransitionException extends WorkflowException
{
    public static function fromStatuses(AppointmentStatus $from, AppointmentStatus $to): self
    {
        return new self(sprintf(
            'La transition de statut "%s" vers "%s" est interdite par le workflow.',
            $from->value,
            $to->value,
        ));
    }
}
