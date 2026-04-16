<?php

declare(strict_types=1);

namespace App\Tests\Service\Availability;

use App\Entity\Appointment;
use App\Entity\Availability;
use App\Entity\MedecinProfile;
use App\Enum\AppointmentStatus;
use App\Service\Availability\AvailabilitySlotCalculator;
use PHPUnit\Framework\TestCase;

final class AvailabilitySlotCalculatorTest extends TestCase
{
    public function testItBuildsSlotsAndSkipsBlockingAppointments(): void
    {
        $doctor = (new MedecinProfile())
            ->setConsultationDuration(30);

        $recurringAvailability = (new Availability())
            ->setIsRecurring(true)
            ->setDayOfWeek(0)
            ->setStartTime(new \DateTimeImmutable('09:00:00'))
            ->setEndTime(new \DateTimeImmutable('10:00:00'));

        $specificAvailability = (new Availability())
            ->setIsRecurring(false)
            ->setSpecificDate(new \DateTimeImmutable('2026-04-06'))
            ->setStartTime(new \DateTimeImmutable('09:00:00'))
            ->setEndTime(new \DateTimeImmutable('10:00:00'));

        $blockingAppointment = (new Appointment())
            ->setDateTime(new \DateTimeImmutable('2026-04-06 09:30:00'))
            ->setEndTime(new \DateTimeImmutable('2026-04-06 10:00:00'))
            ->setStatus(AppointmentStatus::CONFIRMED);

        $cancelledAppointment = (new Appointment())
            ->setDateTime(new \DateTimeImmutable('2026-04-06 09:00:00'))
            ->setEndTime(new \DateTimeImmutable('2026-04-06 09:30:00'))
            ->setStatus(AppointmentStatus::CANCELLED);

        $calculator = new AvailabilitySlotCalculator();
        $slots = $calculator->calculate(
            $doctor,
            new \DateTimeImmutable('2026-04-06'),
            [$recurringAvailability, $specificAvailability],
            [$blockingAppointment, $cancelledAppointment],
        );

        self::assertCount(1, $slots);
        self::assertSame('2026-04-06 09:00', $slots[0]->getStartsAt()->format('Y-m-d H:i'));
        self::assertSame('2026-04-06 09:30', $slots[0]->getEndsAt()->format('Y-m-d H:i'));
    }

    public function testItReturnsNoSlotWhenWindowIsShorterThanConsultationDuration(): void
    {
        $doctor = (new MedecinProfile())
            ->setConsultationDuration(45);

        $availability = (new Availability())
            ->setIsRecurring(true)
            ->setDayOfWeek(0)
            ->setStartTime(new \DateTimeImmutable('09:00:00'))
            ->setEndTime(new \DateTimeImmutable('09:30:00'));

        $calculator = new AvailabilitySlotCalculator();
        $slots = $calculator->calculate(
            $doctor,
            new \DateTimeImmutable('2026-04-06'),
            [$availability],
            [],
        );

        self::assertSame([], $slots);
    }
}
