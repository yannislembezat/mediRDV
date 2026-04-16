<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Appointment;
use App\Entity\Availability;
use App\Entity\Consultation;
use App\Entity\MedecinProfile;
use App\Entity\Notification;
use App\Entity\Prescription;
use App\Entity\PrescriptionItem;
use App\Entity\Specialty;
use App\Entity\User;
use App\Service\Availability\AvailableSlot;

final class ApiResourceNormalizer
{
    /**
     * @return array<string, mixed>
     */
    public function user(User $user): array
    {
        $payload = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'phone' => $user->getPhone(),
            'dateOfBirth' => $user->getDateOfBirth()?->format('Y-m-d'),
            'gender' => $user->getGender()?->value,
            'address' => $user->getAddress(),
            'avatarUrl' => $user->getAvatarUrl(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'createdAt' => $this->formatDateTime($user->getCreatedAt()),
            'updatedAt' => $this->formatDateTime($user->getUpdatedAt()),
        ];

        if ($user->getMedecinProfile() !== null) {
            $payload['medecinProfile'] = $this->medecinSummary($user->getMedecinProfile());
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function specialty(Specialty $specialty): array
    {
        return [
            'id' => $specialty->getId(),
            'name' => $specialty->getName(),
            'slug' => $specialty->getSlug(),
            'icon' => $specialty->getIcon(),
            'description' => $specialty->getDescription(),
            'displayOrder' => $specialty->getDisplayOrder(),
            'isActive' => $specialty->isActive(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function medecinSummary(MedecinProfile $medecin): array
    {
        $doctor = $medecin->getUser();
        $specialty = $medecin->getSpecialty();

        return [
            'id' => $medecin->getId(),
            'fullName' => $medecin->getDisplayName(),
            'firstName' => $doctor?->getFirstName(),
            'lastName' => $doctor?->getLastName(),
            'avatarUrl' => $doctor?->getAvatarUrl(),
            'bio' => $medecin->getBio(),
            'officeLocation' => $medecin->getOfficeLocation(),
            'yearsExperience' => $medecin->getYearsExperience(),
            'consultationDuration' => $medecin->getConsultationDuration(),
            'specialty' => $specialty !== null ? $this->specialty($specialty) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function medecinDetail(MedecinProfile $medecin): array
    {
        return $this->medecinSummary($medecin) + [
            'diploma' => $medecin->getDiploma(),
            'availabilities' => array_map(
                fn (Availability $availability): array => $this->availability($availability),
                $medecin->getAvailabilities()->toArray(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function availability(Availability $availability): array
    {
        return [
            'id' => $availability->getId(),
            'isRecurring' => $availability->isRecurring(),
            'dayOfWeek' => $availability->getDayOfWeek(),
            'specificDate' => $availability->getSpecificDate()?->format('Y-m-d'),
            'startTime' => $availability->getStartTime()->format('H:i:s'),
            'endTime' => $availability->getEndTime()->format('H:i:s'),
            'isActive' => $availability->isActive(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function slot(AvailableSlot $slot): array
    {
        return [
            'dateTime' => $this->formatDateTime($slot->getStartsAt()),
            'endTime' => $this->formatDateTime($slot->getEndsAt()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function appointmentSummary(Appointment $appointment): array
    {
        return [
            'id' => $appointment->getId(),
            'dateTime' => $this->formatDateTime($appointment->getDateTime()),
            'endTime' => $this->formatDateTime($appointment->getEndTime()),
            'status' => $appointment->getStatus()->value,
            'statusLabel' => $appointment->getStatus()->label(),
            'reason' => $appointment->getReason(),
            'medecin' => $this->appointmentDoctor($appointment->getMedecin()),
            'createdAt' => $this->formatDateTime($appointment->getCreatedAt()),
            'canCancel' => $appointment->getStatus()->value === 'pending',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function appointmentDetail(Appointment $appointment): array
    {
        return $this->appointmentSummary($appointment) + [
            'adminNote' => $appointment->getAdminNote(),
            'validatedAt' => $this->formatDateTime($appointment->getValidatedAt()),
            'consultationId' => $appointment->getConsultation()?->getId(),
            'updatedAt' => $this->formatDateTime($appointment->getUpdatedAt()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function medicalRecordSummary(Consultation $consultation): array
    {
        $appointment = $consultation->getAppointment();

        return [
            'id' => $consultation->getId(),
            'diagnosis' => $consultation->getDiagnosis(),
            'completedAt' => $this->formatDateTime($consultation->getCompletedAt()),
            'appointment' => $appointment !== null ? [
                'id' => $appointment->getId(),
                'dateTime' => $this->formatDateTime($appointment->getDateTime()),
                'medecin' => $this->appointmentDoctor($appointment->getMedecin()),
            ] : null,
            'hasPrescription' => $consultation->getPrescription() !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function medicalRecordDetail(Consultation $consultation): array
    {
        return $this->medicalRecordSummary($consultation) + [
            'notes' => $consultation->getNotes(),
            'vitalSigns' => $consultation->getVitalSigns(),
            'prescription' => $consultation->getPrescription() !== null
                ? $this->prescription($consultation->getPrescription())
                : null,
            'createdAt' => $this->formatDateTime($consultation->getCreatedAt()),
            'updatedAt' => $this->formatDateTime($consultation->getUpdatedAt()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function notification(Notification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'title' => $notification->getTitle(),
            'message' => $notification->getMessage(),
            'type' => $notification->getType()->value,
            'typeLabel' => $notification->getType()->label(),
            'referenceId' => $notification->getReferenceId(),
            'isRead' => $notification->isRead(),
            'readAt' => $this->formatDateTime($notification->getReadAt()),
            'createdAt' => $this->formatDateTime($notification->getCreatedAt()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function prescription(Prescription $prescription): array
    {
        return [
            'id' => $prescription->getId(),
            'notes' => $prescription->getNotes(),
            'createdAt' => $this->formatDateTime($prescription->getCreatedAt()),
            'items' => array_map(
                fn (PrescriptionItem $item): array => $this->prescriptionItem($item),
                $prescription->getItems()->toArray(),
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function prescriptionItem(PrescriptionItem $item): array
    {
        return [
            'id' => $item->getId(),
            'name' => $item->getMedication()?->getName() ?? $item->getCustomName(),
            'medicationId' => $item->getMedication()?->getId(),
            'dosage' => $item->getDosage(),
            'duration' => $item->getDuration(),
            'frequency' => $item->getFrequency(),
            'instructions' => $item->getInstructions(),
            'displayOrder' => $item->getDisplayOrder(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function appointmentDoctor(?MedecinProfile $medecin): ?array
    {
        if ($medecin === null) {
            return null;
        }

        return [
            'id' => $medecin->getId(),
            'fullName' => $medecin->getDisplayName(),
            'specialty' => $medecin->getSpecialty()?->getName(),
            'avatarUrl' => $medecin->getUser()?->getAvatarUrl(),
        ];
    }

    private function formatDateTime(?\DateTimeInterface $dateTime): ?string
    {
        return $dateTime?->format(\DateTimeInterface::ATOM);
    }
}
