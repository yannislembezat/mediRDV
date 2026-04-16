<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Enum\AppointmentStatus;
use App\Event\AppointmentStatusChangedEvent;
use App\Service\NotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AppointmentStatusSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AppointmentStatusChangedEvent::class => 'onAppointmentStatusChanged',
        ];
    }

    public function onAppointmentStatusChanged(AppointmentStatusChangedEvent $event): void
    {
        match ($event->getCurrentStatus()) {
            AppointmentStatus::CONFIRMED => $this->notificationService->notifyAppointmentConfirmed($event->getAppointment()),
            AppointmentStatus::REFUSED => $this->notificationService->notifyAppointmentRefused($event->getAppointment()),
            default => null,
        };
    }
}
