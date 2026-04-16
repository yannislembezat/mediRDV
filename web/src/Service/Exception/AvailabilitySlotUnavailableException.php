<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Entity\MedecinProfile;

final class AvailabilitySlotUnavailableException extends WorkflowException
{
    public static function forMedecinAndStartTime(MedecinProfile $medecin, \DateTimeInterface $startsAt): self
    {
        return new self(sprintf(
            'Le créneau du %s à %s n\'est pas disponible pour %s.',
            $startsAt->format('d/m/Y'),
            $startsAt->format('H:i'),
            $medecin->getDisplayName(),
        ));
    }
}
