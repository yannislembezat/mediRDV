<?php

declare(strict_types=1);

namespace App\Service\Availability;

use App\Entity\Appointment;
use App\Entity\Availability;
use App\Entity\MedecinProfile;
use App\Enum\AppointmentStatus;

final class AvailabilitySlotCalculator
{
    /**
     * @param list<Availability> $availabilities
     * @param list<Appointment> $appointments
     *
     * @return list<AvailableSlot>
     */
    public function calculate(
        MedecinProfile $medecin,
        \DateTimeInterface $date,
        array $availabilities,
        array $appointments,
        ?int $excludeAppointmentId = null,
    ): array {
        $normalizedDate = \DateTimeImmutable::createFromInterface($date);
        $timezone = $normalizedDate->getTimezone();
        $durationInMinutes = max(1, $medecin->getConsultationDuration());
        $blockingAppointments = $this->filterBlockingAppointments($appointments, $excludeAppointmentId);
        $slots = [];

        foreach ($availabilities as $availability) {
            $windowStart = new \DateTimeImmutable(sprintf(
                '%s %s',
                $normalizedDate->format('Y-m-d'),
                $availability->getStartTime()->format('H:i:s'),
            ), $timezone);
            $windowEnd = new \DateTimeImmutable(sprintf(
                '%s %s',
                $normalizedDate->format('Y-m-d'),
                $availability->getEndTime()->format('H:i:s'),
            ), $timezone);

            if ($windowEnd <= $windowStart) {
                continue;
            }

            for ($slotStart = $windowStart; true; $slotStart = $slotStart->modify(sprintf('+%d minutes', $durationInMinutes))) {
                $slotEnd = $slotStart->modify(sprintf('+%d minutes', $durationInMinutes));

                if ($slotEnd > $windowEnd) {
                    break;
                }

                if ($this->overlapsBlockingAppointment($slotStart, $slotEnd, $blockingAppointments)) {
                    continue;
                }

                $slot = new AvailableSlot($slotStart, $slotEnd);
                $slots[$slotStart->format(\DateTimeInterface::ATOM)] = $slot;
            }
        }

        ksort($slots);

        return array_values($slots);
    }

    /**
     * @param list<Appointment> $appointments
     *
     * @return list<Appointment>
     */
    private function filterBlockingAppointments(array $appointments, ?int $excludeAppointmentId = null): array
    {
        return array_values(array_filter(
            $appointments,
            static function (Appointment $appointment) use ($excludeAppointmentId): bool {
                if ($excludeAppointmentId !== null && $appointment->getId() === $excludeAppointmentId) {
                    return false;
                }

                return in_array($appointment->getStatus(), [
                    AppointmentStatus::PENDING,
                    AppointmentStatus::CONFIRMED,
                    AppointmentStatus::COMPLETED,
                ], true);
            },
        ));
    }

    /**
     * @param list<Appointment> $appointments
     */
    private function overlapsBlockingAppointment(
        \DateTimeImmutable $slotStart,
        \DateTimeImmutable $slotEnd,
        array $appointments,
    ): bool {
        foreach ($appointments as $appointment) {
            if ($appointment->getDateTime() < $slotEnd && $appointment->getEndTime() > $slotStart) {
                return true;
            }
        }

        return false;
    }
}
