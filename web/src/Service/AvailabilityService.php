<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\MedecinProfile;
use App\Repository\AppointmentRepository;
use App\Repository\AvailabilityRepository;
use App\Service\Availability\AvailabilitySlotCalculator;
use App\Service\Availability\AvailableSlot;
use App\Service\Exception\AvailabilitySlotUnavailableException;

final class AvailabilityService
{
    public function __construct(
        private readonly AvailabilityRepository $availabilityRepository,
        private readonly AppointmentRepository $appointmentRepository,
        private readonly AvailabilitySlotCalculator $slotCalculator,
    ) {
    }

    /**
     * @return list<AvailableSlot>
     */
    public function getFreeSlots(
        MedecinProfile $medecin,
        \DateTimeInterface $date,
        ?int $excludeAppointmentId = null,
    ): array {
        $normalizedDate = \DateTimeImmutable::createFromInterface($date);
        $availabilities = $this->availabilityRepository->findActiveForMedecinOnDate($medecin, $normalizedDate);

        if ($availabilities === []) {
            return [];
        }

        $appointments = $this->appointmentRepository->findForMedecin(
            $medecin,
            null,
            $normalizedDate->setTime(0, 0, 0),
            $normalizedDate->setTime(23, 59, 59),
        );

        return $this->slotCalculator->calculate(
            $medecin,
            $normalizedDate,
            $availabilities,
            $appointments,
            $excludeAppointmentId,
        );
    }

    public function isSlotAvailable(
        MedecinProfile $medecin,
        \DateTimeInterface $startsAt,
        ?int $excludeAppointmentId = null,
    ): bool {
        return $this->findFreeSlot($medecin, $startsAt, $excludeAppointmentId) !== null;
    }

    public function findFreeSlot(
        MedecinProfile $medecin,
        \DateTimeInterface $startsAt,
        ?int $excludeAppointmentId = null,
    ): ?AvailableSlot {
        foreach ($this->getFreeSlots($medecin, $startsAt, $excludeAppointmentId) as $slot) {
            if ($slot->matchesStart($startsAt)) {
                return $slot;
            }
        }

        return null;
    }

    public function requireFreeSlot(
        MedecinProfile $medecin,
        \DateTimeInterface $startsAt,
        ?int $excludeAppointmentId = null,
    ): AvailableSlot {
        $slot = $this->findFreeSlot($medecin, $startsAt, $excludeAppointmentId);

        if ($slot === null) {
            throw AvailabilitySlotUnavailableException::forMedecinAndStartTime($medecin, $startsAt);
        }

        return $slot;
    }
}
