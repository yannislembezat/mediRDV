<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ORM\Table(name: 'appointment')]
#[ORM\Index(name: 'idx_appt_patient', columns: ['patient_id'])]
#[ORM\Index(name: 'idx_appt_medecin', columns: ['medecin_id'])]
#[ORM\Index(name: 'idx_appt_status', columns: ['status'])]
#[ORM\Index(name: 'idx_appt_datetime', columns: ['date_time'])]
#[ORM\Index(name: 'idx_appt_medecin_date', columns: ['medecin_id', 'date_time'])]
#[ORM\HasLifecycleCallbacks]
final class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'patientAppointments', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $patient = null;

    #[ORM\ManyToOne(inversedBy: 'appointments', targetEntity: MedecinProfile::class)]
    #[ORM\JoinColumn(name: 'medecin_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?MedecinProfile $medecin = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'date_time')]
    private \DateTimeImmutable $dateTime;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'end_time')]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(length: 20, enumType: AppointmentStatus::class, options: ['default' => 'pending'])]
    private AppointmentStatus $status = AppointmentStatus::PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'admin_note')]
    private ?string $adminNote = null;

    #[ORM\ManyToOne(inversedBy: 'validatedAppointments', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'validated_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $validatedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, name: 'validated_at')]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToOne(mappedBy: 'appointment', targetEntity: Consultation::class, cascade: ['persist', 'remove'])]
    private ?Consultation $consultation = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->dateTime = $now;
        $this->endTime = $now->modify('+30 minutes');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?User
    {
        return $this->patient;
    }

    public function setPatient(?User $patient): self
    {
        $this->patient = $patient;

        return $this;
    }

    public function getMedecin(): ?MedecinProfile
    {
        return $this->medecin;
    }

    public function setMedecin(?MedecinProfile $medecin): self
    {
        $this->medecin = $medecin;

        return $this;
    }

    public function getDateTime(): \DateTimeImmutable
    {
        return $this->dateTime;
    }

    public function setDateTime(\DateTimeImmutable $dateTime): self
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getStatus(): AppointmentStatus
    {
        return $this->status;
    }

    public function setStatus(AppointmentStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason !== null ? trim($reason) : null;

        return $this;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function setAdminNote(?string $adminNote): self
    {
        $this->adminNote = $adminNote !== null ? trim($adminNote) : null;

        return $this;
    }

    public function getValidatedBy(): ?User
    {
        return $this->validatedBy;
    }

    public function setValidatedBy(?User $validatedBy): self
    {
        $this->validatedBy = $validatedBy;

        return $this;
    }

    public function getValidatedAt(): ?\DateTimeImmutable
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTimeImmutable $validatedAt): self
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): self
    {
        $this->consultation = $consultation;

        if ($consultation !== null && $consultation->getAppointment() !== $this) {
            $consultation->setAppointment($this);
        }

        return $this;
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $this->createdAt ?? $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
