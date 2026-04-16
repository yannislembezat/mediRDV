<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Consultation;
use Symfony\Component\Validator\Constraints as Assert;

final class DoctorConsultationData
{
    #[Assert\Length(max: 10000, maxMessage: 'Les notes ne peuvent pas depasser {{ limit }} caracteres.')]
    public ?string $notes = null;

    #[Assert\Length(max: 500, maxMessage: 'Le diagnostic ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $diagnosis = null;

    #[Assert\Length(max: 50, maxMessage: 'La tension ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $bloodPressure = null;

    #[Assert\Length(max: 50, maxMessage: 'La frequence cardiaque ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $heartRate = null;

    #[Assert\Length(max: 50, maxMessage: 'La temperature ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $temperature = null;

    #[Assert\Length(max: 50, maxMessage: 'Le poids ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $weight = null;

    #[Assert\Length(max: 50, maxMessage: 'La saturation ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $oxygenSaturation = null;

    public static function fromConsultation(Consultation $consultation): self
    {
        $data = new self();
        $vitalSigns = $consultation->getVitalSigns() ?? [];

        $data->notes = $consultation->getNotes();
        $data->diagnosis = $consultation->getDiagnosis();
        $data->bloodPressure = self::resolveVitalSign($vitalSigns, ['blood_pressure', 'bloodPressure']);
        $data->heartRate = self::resolveVitalSign($vitalSigns, ['heart_rate', 'heartRate']);
        $data->temperature = self::resolveVitalSign($vitalSigns, ['temperature']);
        $data->weight = self::resolveVitalSign($vitalSigns, ['weight']);
        $data->oxygenSaturation = self::resolveVitalSign($vitalSigns, ['oxygen_saturation', 'oxygenSaturation']);

        return $data;
    }

    /**
     * @return array{notes: ?string, diagnosis: ?string, vitalSigns: ?array<string, string|null>}
     */
    public function toPayload(): array
    {
        $vitalSigns = array_filter([
            'blood_pressure' => $this->normalizeOptionalString($this->bloodPressure),
            'heart_rate' => $this->normalizeOptionalString($this->heartRate),
            'temperature' => $this->normalizeOptionalString($this->temperature),
            'weight' => $this->normalizeOptionalString($this->weight),
            'oxygen_saturation' => $this->normalizeOptionalString($this->oxygenSaturation),
        ], static fn (?string $value): bool => $value !== null);

        return [
            'notes' => $this->normalizeOptionalString($this->notes),
            'diagnosis' => $this->normalizeOptionalString($this->diagnosis),
            'vitalSigns' => $vitalSigns !== [] ? $vitalSigns : null,
        ];
    }

    /**
     * @param array<string, mixed> $vitalSigns
     * @param list<string> $keys
     */
    private static function resolveVitalSign(array $vitalSigns, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $vitalSigns[$key] ?? null;

            if (is_scalar($value)) {
                $normalizedValue = trim((string) $value);

                if ($normalizedValue !== '') {
                    return $normalizedValue;
                }
            }
        }

        return null;
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
