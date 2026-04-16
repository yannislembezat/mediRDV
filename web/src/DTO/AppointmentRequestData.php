<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class AppointmentRequestData
{
    #[Assert\NotNull(message: 'Le medecin est obligatoire.')]
    #[Assert\Positive(message: 'Le medecin selectionne est invalide.')]
    public ?int $medecinId = null;

    #[Assert\NotBlank(message: 'La date du rendez-vous est obligatoire.')]
    public string $dateTime = '';

    #[Assert\Length(max: 2000, maxMessage: 'Le motif ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $reason = null;

    public function getDateTimeAsDateTimeImmutable(): \DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($this->dateTime);
        } catch (\Exception) {
            throw new \InvalidArgumentException('La date du rendez-vous doit etre un datetime ISO-8601 valide.');
        }
    }
}
