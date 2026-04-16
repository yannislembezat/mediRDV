<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'prescription_item')]
#[ORM\Index(name: 'idx_pi_prescription', columns: ['prescription_id'])]
#[ORM\Index(name: 'idx_pi_medication', columns: ['medication_id'])]
final class PrescriptionItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items', targetEntity: Prescription::class)]
    #[ORM\JoinColumn(name: 'prescription_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Prescription $prescription = null;

    #[ORM\ManyToOne(inversedBy: 'prescriptionItems', targetEntity: Medication::class)]
    #[ORM\JoinColumn(name: 'medication_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Medication $medication = null;

    #[ORM\Column(length: 200, nullable: true, name: 'custom_name')]
    private ?string $customName = null;

    #[ORM\Column(length: 150)]
    private string $dosage = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $duration = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $frequency = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $instructions = null;

    #[ORM\Column(name: 'display_order', options: ['default' => 0])]
    private int $displayOrder = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrescription(): ?Prescription
    {
        return $this->prescription;
    }

    public function setPrescription(?Prescription $prescription): self
    {
        $this->prescription = $prescription;

        return $this;
    }

    public function getMedication(): ?Medication
    {
        return $this->medication;
    }

    public function setMedication(?Medication $medication): self
    {
        $this->medication = $medication;

        return $this;
    }

    public function getCustomName(): ?string
    {
        return $this->customName;
    }

    public function setCustomName(?string $customName): self
    {
        $this->customName = $customName !== null ? trim($customName) : null;

        return $this;
    }

    public function getDosage(): string
    {
        return $this->dosage;
    }

    public function setDosage(string $dosage): self
    {
        $this->dosage = trim($dosage);

        return $this;
    }

    public function getDuration(): ?string
    {
        return $this->duration;
    }

    public function setDuration(?string $duration): self
    {
        $this->duration = $duration !== null ? trim($duration) : null;

        return $this;
    }

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(?string $frequency): self
    {
        $this->frequency = $frequency !== null ? trim($frequency) : null;

        return $this;
    }

    public function getInstructions(): ?string
    {
        return $this->instructions;
    }

    public function setInstructions(?string $instructions): self
    {
        $this->instructions = $instructions !== null ? trim($instructions) : null;

        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
