<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Consultation;
use App\Entity\MedecinProfile;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Enum\UserRole;
use App\Event\AppointmentStatusChangedEvent;
use App\Service\Appointment\AppointmentStatusTransitionGuard;
use App\Service\Exception\WorkflowException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class AppointmentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AvailabilityService $availabilityService,
        private readonly AppointmentStatusTransitionGuard $transitionGuard,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function request(
        User $patient,
        MedecinProfile $medecin,
        \DateTimeInterface $startsAt,
        ?string $reason = null,
    ): Appointment {
        $this->assertRole($patient, UserRole::PATIENT, 'Seul un patient peut demander un rendez-vous.');

        if (!$patient->isActive()) {
            throw new WorkflowException('Le compte patient doit être actif pour demander un rendez-vous.');
        }

        if (($doctor = $medecin->getUser()) === null || !$doctor->isActive()) {
            throw new WorkflowException('Le médecin sélectionné n\'est pas disponible.');
        }

        $slot = $this->availabilityService->requireFreeSlot($medecin, $startsAt);

        $appointment = (new Appointment())
            ->setPatient($patient)
            ->setMedecin($medecin)
            ->setDateTime($slot->getStartsAt())
            ->setEndTime($slot->getEndsAt())
            ->setStatus(AppointmentStatus::PENDING)
            ->setReason($reason)
            ->setAdminNote(null)
            ->setValidatedBy(null)
            ->setValidatedAt(null);

        $this->entityManager->persist($appointment);
        $this->entityManager->flush();

        return $appointment;
    }

    public function cancelByPatient(Appointment $appointment, User $patient): Appointment
    {
        $this->assertRole($patient, UserRole::PATIENT, 'Seul un patient peut annuler un rendez-vous.');

        if ($appointment->getPatient()?->getId() !== $patient->getId()) {
            throw new WorkflowException('Le patient courant ne peut annuler que ses propres rendez-vous.');
        }

        if ($appointment->getStatus() !== AppointmentStatus::PENDING) {
            throw new WorkflowException('Le patient ne peut annuler qu\'un rendez-vous encore en attente.');
        }

        return $this->transition($appointment, AppointmentStatus::CANCELLED);
    }

    public function approve(Appointment $appointment, User $admin, ?string $adminNote = null): Appointment
    {
        $this->assertRole($admin, UserRole::ADMIN, 'Seul un administrateur peut approuver un rendez-vous.');
        $medecin = $appointment->getMedecin();

        if ($medecin === null) {
            throw new WorkflowException('Le rendez-vous n\'est lié à aucun médecin.');
        }

        $slot = $this->availabilityService->requireFreeSlot($medecin, $appointment->getDateTime(), $appointment->getId());

        $appointment
            ->setDateTime($slot->getStartsAt())
            ->setEndTime($slot->getEndsAt())
            ->setAdminNote($adminNote)
            ->setValidatedBy($admin)
            ->setValidatedAt(new \DateTimeImmutable());

        return $this->transition($appointment, AppointmentStatus::CONFIRMED);
    }

    public function refuse(Appointment $appointment, User $admin, string $adminNote): Appointment
    {
        $this->assertRole($admin, UserRole::ADMIN, 'Seul un administrateur peut refuser un rendez-vous.');

        if (trim($adminNote) === '') {
            throw new WorkflowException('Un motif de refus est obligatoire.');
        }

        $appointment
            ->setAdminNote($adminNote)
            ->setValidatedBy($admin)
            ->setValidatedAt(new \DateTimeImmutable());

        return $this->transition($appointment, AppointmentStatus::REFUSED);
    }

    public function completeFromFinalizedConsultation(Consultation $consultation): Appointment
    {
        $appointment = $consultation->getAppointment();

        if ($appointment === null) {
            throw new WorkflowException('La consultation doit être rattachée à un rendez-vous pour être finalisée.');
        }

        if (!$consultation->isCompleted() || $consultation->getCompletedAt() === null) {
            throw new WorkflowException('Le rendez-vous ne peut être complété qu\'une fois la consultation finalisée.');
        }

        return $this->transition($appointment, AppointmentStatus::COMPLETED);
    }

    private function transition(Appointment $appointment, AppointmentStatus $targetStatus): Appointment
    {
        $currentStatus = $appointment->getStatus();
        $this->transitionGuard->assertCanTransition($currentStatus, $targetStatus);

        $appointment->setStatus($targetStatus);

        $this->entityManager->flush();
        $this->eventDispatcher->dispatch(new AppointmentStatusChangedEvent($appointment, $currentStatus, $targetStatus));

        return $appointment;
    }

    private function assertRole(User $user, UserRole $role, string $message): void
    {
        if (!$user->hasRole($role)) {
            throw new WorkflowException($message);
        }
    }
}
