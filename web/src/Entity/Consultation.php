<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
#[ORM\Table(name: 'consultation')]
#[ORM\UniqueConstraint(name: 'uk_consultation_appt', columns: ['appointment_id'])]
#[ORM\HasLifecycleCallbacks]
final class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'consultation', targetEntity: Appointment::class)]
    #[ORM\JoinColumn(name: 'appointment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Appointment $appointment = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $diagnosis = null;

    #[ORM\Column(type: Types::JSON, nullable: true, name: 'vital_signs')]
    private ?array $vitalSigns = null;

    #[ORM\Column(name: 'is_completed', options: ['default' => false])]
    private bool $isCompleted = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, name: 'completed_at')]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToOne(mappedBy: 'consultation', targetEntity: Prescription::class, cascade: ['persist', 'remove'])]
    private ?Prescription $prescription = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointment $appointment): self
    {
        $this->appointment = $appointment;

        if ($appointment !== null && $appointment->getConsultation() !== $this) {
            $appointment->setConsultation($this);
        }

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes !== null ? trim($notes) : null;

        return $this;
    }

    public function getDiagnosis(): ?string
    {
        return $this->diagnosis;
    }

    public function setDiagnosis(?string $diagnosis): self
    {
        $this->diagnosis = $diagnosis !== null ? trim($diagnosis) : null;

        return $this;
    }

    public function getVitalSigns(): ?array
    {
        return $this->vitalSigns;
    }

    public function setVitalSigns(?array $vitalSigns): self
    {
        $this->vitalSigns = $vitalSigns;

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): self
    {
        $this->isCompleted = $isCompleted;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;

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

    public function getPrescription(): ?Prescription
    {
        return $this->prescription;
    }

    public function setPrescription(?Prescription $prescription): self
    {
        $this->prescription = $prescription;

        if ($prescription !== null && $prescription->getConsultation() !== $this) {
            $prescription->setConsultation($this);
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
