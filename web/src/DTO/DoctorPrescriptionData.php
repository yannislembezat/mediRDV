<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Consultation;
use App\Entity\Prescription;
use App\Service\Prescription\PrescriptionItemData;
use Symfony\Component\Validator\Constraints as Assert;

final class DoctorPrescriptionData
{
    private const MINIMUM_ROWS = 2;

    #[Assert\Length(max: 10000, maxMessage: 'Les notes ne peuvent pas depasser {{ limit }} caracteres.')]
    public ?string $notes = null;

    /**
     * @var list<DoctorPrescriptionItemData>
     */
    #[Assert\Valid]
    public array $items = [];

    public static function fromConsultation(Consultation $consultation): self
    {
        $data = new self();
        $prescription = $consultation->getPrescription();

        if ($prescription instanceof Prescription) {
            $data->notes = $prescription->getNotes();

            foreach ($prescription->getItems() as $item) {
                $data->items[] = DoctorPrescriptionItemData::fromPrescriptionItem($item);
            }
        }

        $data->ensureMinimumRows();

        return $data;
    }

    public function getNormalizedNotes(): ?string
    {
        return $this->normalizeOptionalString($this->notes);
    }

    /**
     * @return list<PrescriptionItemData>
     */
    public function toServiceItems(): array
    {
        $items = [];

        foreach ($this->items as $index => $item) {
            if (!$item instanceof DoctorPrescriptionItemData) {
                continue;
            }

            $normalizedItem = $item->toServiceData($index);

            if ($normalizedItem instanceof PrescriptionItemData) {
                $items[] = $normalizedItem;
            }
        }

        return $items;
    }

    public function ensureMinimumRows(int $minimumRows = self::MINIMUM_ROWS): void
    {
        while (count($this->items) < $minimumRows) {
            $this->items[] = DoctorPrescriptionItemData::createEmpty();
        }
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
