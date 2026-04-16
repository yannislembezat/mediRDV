<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Consultation;
use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use Doctrine\ORM\EntityManagerInterface;

final class NotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function create(
        User $recipient,
        NotificationType $type,
        string $title,
        string $message,
        ?int $referenceId = null,
        bool $flush = true,
    ): Notification {
        $notification = (new Notification())
            ->setUser($recipient)
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setReferenceId($referenceId);

        $this->entityManager->persist($notification);

        if ($flush) {
            $this->entityManager->flush();
        }

        return $notification;
    }

    /**
     * @return list<Notification>
     */
    public function notifyAppointmentConfirmed(Appointment $appointment): array
    {
        $notifications = [];
        $doctorName = $appointment->getMedecin()?->getDisplayName() ?? 'le médecin';
        $patientName = $appointment->getPatient()?->getFullName() ?? 'le patient';
        $formattedDate = $this->formatDateTime($appointment->getDateTime());
        $referenceId = $appointment->getId();

        if (($patient = $appointment->getPatient()) !== null) {
            $notifications[] = $this->create(
                $patient,
                NotificationType::APPOINTMENT_CONFIRMED,
                'Rendez-vous confirmé',
                sprintf('Votre rendez-vous du %s avec %s a été confirmé.', $formattedDate, $doctorName),
                $referenceId,
                false,
            );
        }

        if (($doctor = $appointment->getMedecin()?->getUser()) !== null) {
            $notifications[] = $this->create(
                $doctor,
                NotificationType::APPOINTMENT_CONFIRMED,
                'Rendez-vous confirmé',
                sprintf('Le rendez-vous de %s prévu le %s a été confirmé.', $patientName, $formattedDate),
                $referenceId,
                false,
            );
        }

        if ($notifications !== []) {
            $this->entityManager->flush();
        }

        return $notifications;
    }

    /**
     * @return list<Notification>
     */
    public function notifyAppointmentRefused(Appointment $appointment): array
    {
        $notifications = [];
        $doctorName = $appointment->getMedecin()?->getDisplayName() ?? 'le médecin';
        $patientName = $appointment->getPatient()?->getFullName() ?? 'le patient';
        $formattedDate = $this->formatDateTime($appointment->getDateTime());
        $referenceId = $appointment->getId();
        $reasonSuffix = $appointment->getAdminNote() !== null && $appointment->getAdminNote() !== ''
            ? sprintf(' Motif : %s.', $appointment->getAdminNote())
            : '';

        if (($patient = $appointment->getPatient()) !== null) {
            $notifications[] = $this->create(
                $patient,
                NotificationType::APPOINTMENT_REFUSED,
                'Rendez-vous refusé',
                sprintf('Votre demande de rendez-vous du %s avec %s a été refusée.%s', $formattedDate, $doctorName, $reasonSuffix),
                $referenceId,
                false,
            );
        }

        if (($doctor = $appointment->getMedecin()?->getUser()) !== null) {
            $notifications[] = $this->create(
                $doctor,
                NotificationType::APPOINTMENT_REFUSED,
                'Rendez-vous refusé',
                sprintf('La demande de rendez-vous de %s prévue le %s a été refusée.', $patientName, $formattedDate),
                $referenceId,
                false,
            );
        }

        if ($notifications !== []) {
            $this->entityManager->flush();
        }

        return $notifications;
    }

    public function notifyConsultationReady(Consultation $consultation): ?Notification
    {
        $appointment = $consultation->getAppointment();

        if ($appointment === null || $appointment->getPatient() === null) {
            return null;
        }

        return $this->create(
            $appointment->getPatient(),
            NotificationType::CONSULTATION_READY,
            'Consultation disponible',
            sprintf(
                'Votre consultation du %s avec %s est disponible dans votre dossier médical.',
                $this->formatDateTime($appointment->getDateTime()),
                $appointment->getMedecin()?->getDisplayName() ?? 'le médecin',
            ),
            $consultation->getId(),
        );
    }

    private function formatDateTime(\DateTimeInterface $dateTime): string
    {
        return sprintf('%s à %s', $dateTime->format('d/m/Y'), $dateTime->format('H:i'));
    }
}
