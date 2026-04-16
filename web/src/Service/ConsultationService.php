<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Consultation;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Enum\UserRole;
use App\Service\Exception\WorkflowException;
use Doctrine\ORM\EntityManagerInterface;

final class ConsultationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AppointmentService $appointmentService,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function open(Appointment $appointment, User $doctor): Consultation
    {
        $this->assertDoctorCanManageAppointment($appointment, $doctor);

        if ($appointment->getStatus() !== AppointmentStatus::CONFIRMED) {
            throw new WorkflowException('Une consultation ne peut être ouverte que pour un rendez-vous confirmé.');
        }

        if (($consultation = $appointment->getConsultation()) !== null) {
            return $consultation;
        }

        $consultation = (new Consultation())
            ->setAppointment($appointment);

        $this->entityManager->persist($consultation);
        $this->entityManager->flush();

        return $consultation;
    }

    /**
     * @param array{notes?: ?string, diagnosis?: ?string, vitalSigns?: ?array<string, scalar|null>} $data
     */
    public function update(Consultation $consultation, User $doctor, array $data): Consultation
    {
        $appointment = $consultation->getAppointment();

        if ($appointment === null) {
            throw new WorkflowException('La consultation doit être rattachée à un rendez-vous.');
        }

        $this->assertDoctorCanManageAppointment($appointment, $doctor);

        if ($consultation->isCompleted()) {
            throw new WorkflowException('Une consultation finalisée ne peut plus être modifiée.');
        }

        $this->applyData($consultation, $data);
        $this->entityManager->flush();

        return $consultation;
    }

    /**
     * @param array{notes?: ?string, diagnosis?: ?string, vitalSigns?: ?array<string, scalar|null>} $data
     */
    public function finalize(Consultation $consultation, User $doctor, array $data = []): Consultation
    {
        $appointment = $consultation->getAppointment();

        if ($appointment === null) {
            throw new WorkflowException('La consultation doit être rattachée à un rendez-vous.');
        }

        $this->assertDoctorCanManageAppointment($appointment, $doctor);

        if ($appointment->getStatus() !== AppointmentStatus::CONFIRMED) {
            throw new WorkflowException('Seul un rendez-vous confirmé peut être finalisé.');
        }

        if ($consultation->isCompleted()) {
            throw new WorkflowException('La consultation est déjà finalisée.');
        }

        $this->applyData($consultation, $data);
        $consultation
            ->setIsCompleted(true)
            ->setCompletedAt(new \DateTimeImmutable());

        $this->appointmentService->completeFromFinalizedConsultation($consultation);
        $this->notificationService->notifyConsultationReady($consultation);

        return $consultation;
    }

    /**
     * @param array{notes?: ?string, diagnosis?: ?string, vitalSigns?: ?array<string, scalar|null>} $data
     */
    private function applyData(Consultation $consultation, array $data): void
    {
        if (array_key_exists('notes', $data)) {
            $notes = $data['notes'];

            if ($notes !== null && !is_string($notes)) {
                throw new \InvalidArgumentException('Le champ "notes" doit être une chaîne ou null.');
            }

            $consultation->setNotes($notes);
        }

        if (array_key_exists('diagnosis', $data)) {
            $diagnosis = $data['diagnosis'];

            if ($diagnosis !== null && !is_string($diagnosis)) {
                throw new \InvalidArgumentException('Le champ "diagnosis" doit être une chaîne ou null.');
            }

            $consultation->setDiagnosis($diagnosis);
        }

        if (array_key_exists('vitalSigns', $data)) {
            $vitalSigns = $data['vitalSigns'];

            if ($vitalSigns !== null && !is_array($vitalSigns)) {
                throw new \InvalidArgumentException('Le champ "vitalSigns" doit être un tableau ou null.');
            }

            $consultation->setVitalSigns($this->normalizeVitalSigns($vitalSigns));
        }
    }

    /**
     * @param ?array<string, scalar|null> $vitalSigns
     *
     * @return ?array<string, string|null>
     */
    private function normalizeVitalSigns(?array $vitalSigns): ?array
    {
        if ($vitalSigns === null) {
            return null;
        }

        $normalized = [];

        foreach ($vitalSigns as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                continue;
            }

            if ($value !== null && !is_scalar($value)) {
                throw new \InvalidArgumentException('Les constantes vitales doivent contenir uniquement des valeurs scalaires ou null.');
            }

            $normalized[trim($key)] = $value !== null ? trim((string) $value) : null;
        }

        return $normalized === [] ? null : $normalized;
    }

    private function assertDoctorCanManageAppointment(Appointment $appointment, User $doctor): void
    {
        if (!$doctor->hasRole(UserRole::MEDECIN)) {
            throw new WorkflowException('Seul un médecin peut gérer une consultation.');
        }

        if ($appointment->getMedecin()?->getUser()?->getId() !== $doctor->getId()) {
            throw new WorkflowException('Le médecin courant ne peut gérer que ses propres rendez-vous.');
        }
    }
}
