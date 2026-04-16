<?php

declare(strict_types=1);

namespace App\Tests\Service\Appointment;

use App\Enum\AppointmentStatus;
use App\Service\Appointment\AppointmentStatusTransitionGuard;
use App\Service\Exception\InvalidAppointmentStatusTransitionException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AppointmentStatusTransitionGuardTest extends TestCase
{
    private AppointmentStatusTransitionGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new AppointmentStatusTransitionGuard();
    }

    #[DataProvider('allowedTransitionsProvider')]
    public function testItAllowsOnlyReportTransitions(AppointmentStatus $from, AppointmentStatus $to): void
    {
        self::assertTrue($this->guard->canTransition($from, $to));
        $this->guard->assertCanTransition($from, $to);
        self::assertContains($to, $this->guard->allowedTargetsFor($from));
    }

    #[DataProvider('forbiddenTransitionsProvider')]
    public function testItRejectsForbiddenTransitions(AppointmentStatus $from, AppointmentStatus $to): void
    {
        self::assertFalse($this->guard->canTransition($from, $to));

        $this->expectException(InvalidAppointmentStatusTransitionException::class);
        $this->guard->assertCanTransition($from, $to);
    }

    /**
     * @return iterable<string, array{0: AppointmentStatus, 1: AppointmentStatus}>
     */
    public static function allowedTransitionsProvider(): iterable
    {
        yield 'pending_to_confirmed' => [AppointmentStatus::PENDING, AppointmentStatus::CONFIRMED];
        yield 'pending_to_refused' => [AppointmentStatus::PENDING, AppointmentStatus::REFUSED];
        yield 'pending_to_cancelled' => [AppointmentStatus::PENDING, AppointmentStatus::CANCELLED];
        yield 'confirmed_to_completed' => [AppointmentStatus::CONFIRMED, AppointmentStatus::COMPLETED];
    }

    /**
     * @return iterable<string, array{0: AppointmentStatus, 1: AppointmentStatus}>
     */
    public static function forbiddenTransitionsProvider(): iterable
    {
        yield 'confirmed_to_cancelled' => [AppointmentStatus::CONFIRMED, AppointmentStatus::CANCELLED];
        yield 'confirmed_to_refused' => [AppointmentStatus::CONFIRMED, AppointmentStatus::REFUSED];
        yield 'completed_to_confirmed' => [AppointmentStatus::COMPLETED, AppointmentStatus::CONFIRMED];
        yield 'refused_to_completed' => [AppointmentStatus::REFUSED, AppointmentStatus::COMPLETED];
        yield 'cancelled_to_pending' => [AppointmentStatus::CANCELLED, AppointmentStatus::PENDING];
    }
}
