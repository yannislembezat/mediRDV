<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\MedecinProfile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class AdminAvailabilityData
{
    public const MODE_RECURRING = 'recurring';
    public const MODE_SPECIFIC = 'specific';

    #[Assert\NotNull(message: 'Le medecin est obligatoire.')]
    public ?MedecinProfile $medecin = null;

    #[Assert\NotBlank(message: 'Le type de disponibilite est obligatoire.')]
    #[Assert\Choice(
        choices: [self::MODE_RECURRING, self::MODE_SPECIFIC],
        message: 'Le type de disponibilite selectionne est invalide.',
    )]
    public string $mode = self::MODE_RECURRING;

    public ?int $dayOfWeek = null;

    #[Assert\Date(message: 'La date specifique doit etre au format AAAA-MM-JJ.')]
    public ?string $specificDate = null;

    #[Assert\NotBlank(message: 'L\'heure de debut est obligatoire.')]
    public string $startTime = '09:00';

    #[Assert\NotBlank(message: 'L\'heure de fin est obligatoire.')]
    public string $endTime = '09:30';

    /**
     * @return array<string, string>
     */
    public static function modeChoices(): array
    {
        return [
            'Hebdomadaire' => self::MODE_RECURRING,
            'Date precise' => self::MODE_SPECIFIC,
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function dayChoices(): array
    {
        return [
            'Lundi' => 0,
            'Mardi' => 1,
            'Mercredi' => 2,
            'Jeudi' => 3,
            'Vendredi' => 4,
            'Samedi' => 5,
            'Dimanche' => 6,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function dayLabels(): array
    {
        return array_flip(self::dayChoices());
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        if ($this->mode === self::MODE_RECURRING && $this->dayOfWeek === null) {
            $context->buildViolation('Selectionnez un jour de semaine pour un creneau recurrent.')
                ->atPath('dayOfWeek')
                ->addViolation();
        }

        if ($this->mode === self::MODE_SPECIFIC && $this->normalizeOptionalString($this->specificDate) === null) {
            $context->buildViolation('Selectionnez une date pour un creneau ponctuel.')
                ->atPath('specificDate')
                ->addViolation();
        }

        $startTime = $this->parseTime($this->startTime);
        $endTime = $this->parseTime($this->endTime);

        if ($startTime === null) {
            $context->buildViolation('L\'heure de debut est invalide.')
                ->atPath('startTime')
                ->addViolation();
        }

        if ($endTime === null) {
            $context->buildViolation('L\'heure de fin est invalide.')
                ->atPath('endTime')
                ->addViolation();
        }

        if ($startTime instanceof \DateTimeImmutable && $endTime instanceof \DateTimeImmutable && $endTime <= $startTime) {
            $context->buildViolation('L\'heure de fin doit etre posterieure a l\'heure de debut.')
                ->atPath('endTime')
                ->addViolation();
        }
    }

    public function isRecurring(): bool
    {
        return $this->mode === self::MODE_RECURRING;
    }

    public function getSpecificDateAsDateTimeImmutable(): ?\DateTimeImmutable
    {
        $specificDate = $this->normalizeOptionalString($this->specificDate);

        if ($specificDate === null) {
            return null;
        }

        $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $specificDate);

        if (!$parsedDate instanceof \DateTimeImmutable) {
            throw new \InvalidArgumentException('La date specifique fournie est invalide.');
        }

        return $parsedDate;
    }

    public function getStartTimeAsDateTimeImmutable(): \DateTimeImmutable
    {
        return $this->parseTime($this->startTime) ?? throw new \InvalidArgumentException('L\'heure de debut fournie est invalide.');
    }

    public function getEndTimeAsDateTimeImmutable(): \DateTimeImmutable
    {
        return $this->parseTime($this->endTime) ?? throw new \InvalidArgumentException('L\'heure de fin fournie est invalide.');
    }

    private function parseTime(?string $value): ?\DateTimeImmutable
    {
        $normalizedValue = $this->normalizeOptionalString($value);

        if ($normalizedValue === null) {
            return null;
        }

        $formats = ['!H:i', '!H:i:s'];

        foreach ($formats as $format) {
            $parsedTime = \DateTimeImmutable::createFromFormat($format, $normalizedValue);

            if ($parsedTime instanceof \DateTimeImmutable) {
                return $parsedTime;
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
