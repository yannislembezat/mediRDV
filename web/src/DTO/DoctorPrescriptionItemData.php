<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Medication;
use App\Entity\PrescriptionItem;
use App\Service\Prescription\PrescriptionItemData;
use Symfony\Component\Validator\Constraints as Assert;

final class DoctorPrescriptionItemData
{
    public ?Medication $medication = null;

    #[Assert\Length(max: 200, maxMessage: 'Le nom libre ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $customName = null;

    #[Assert\Length(max: 150, maxMessage: 'La posologie ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $dosage = null;

    #[Assert\Length(max: 100, maxMessage: 'La duree ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $duration = null;

    #[Assert\Length(max: 100, maxMessage: 'La frequence ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $frequency = null;

    #[Assert\Length(max: 2000, maxMessage: 'Les instructions ne peuvent pas depasser {{ limit }} caracteres.')]
    public ?string $instructions = null;

    public static function createEmpty(): self
    {
        return new self();
    }

    public static function fromPrescriptionItem(PrescriptionItem $item): self
    {
        $data = new self();
        $data->medication = $item->getMedication();
        $data->customName = $item->getCustomName();
        $data->dosage = $item->getDosage();
        $data->duration = $item->getDuration();
        $data->frequency = $item->getFrequency();
        $data->instructions = $item->getInstructions();

        return $data;
    }

    public function isEmpty(): bool
    {
        return $this->medication === null
            && $this->normalizeOptionalString($this->customName) === null
            && $this->normalizeOptionalString($this->dosage) === null
            && $this->normalizeOptionalString($this->duration) === null
            && $this->normalizeOptionalString($this->frequency) === null
            && $this->normalizeOptionalString($this->instructions) === null;
    }

    public function toServiceData(int $displayOrder): ?PrescriptionItemData
    {
        if ($this->isEmpty()) {
            return null;
        }

        return new PrescriptionItemData(
            $this->medication,
            $this->normalizeOptionalString($this->customName),
            $this->normalizeOptionalString($this->dosage) ?? '',
            $this->normalizeOptionalString($this->duration),
            $this->normalizeOptionalString($this->frequency),
            $this->normalizeOptionalString($this->instructions),
            $displayOrder,
        );
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalizedValue = trim($value);

        return $normalizedValue !== '' ? $normalizedValue : null;
    }

}
