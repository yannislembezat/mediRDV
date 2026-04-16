<?php

declare(strict_types=1);

namespace App\Service\Prescription;

use App\Entity\Medication;

final readonly class PrescriptionItemData
{
    public ?Medication $medication;
    public ?string $customName;
    public string $dosage;
    public ?string $duration;
    public ?string $frequency;
    public ?string $instructions;
    public ?int $displayOrder;

    public function __construct(
        ?Medication $medication,
        ?string $customName,
        string $dosage,
        ?string $duration = null,
        ?string $frequency = null,
        ?string $instructions = null,
        ?int $displayOrder = null,
    ) {
        $normalizedCustomName = $customName !== null ? trim($customName) : null;
        $normalizedDosage = trim($dosage);

        $this->medication = $medication;
        $this->customName = $normalizedCustomName;
        $this->dosage = $normalizedDosage;
        $this->duration = $duration !== null ? trim($duration) : null;
        $this->frequency = $frequency !== null ? trim($frequency) : null;
        $this->instructions = $instructions !== null ? trim($instructions) : null;
        $this->displayOrder = $displayOrder !== null ? max(0, $displayOrder) : null;
    }
}
