<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\Gender;
use Symfony\Component\Validator\Constraints as Assert;

final class PatientProfileUpdateData
{
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'Veuillez saisir une adresse email valide.')]
    #[Assert\Length(max: 180, maxMessage: 'L\'email ne peut pas depasser {{ limit }} caracteres.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Le prenom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le prenom ne peut pas depasser {{ limit }} caracteres.')]
    public string $firstName = '';

    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas depasser {{ limit }} caracteres.')]
    public string $lastName = '';

    #[Assert\Length(max: 20, maxMessage: 'Le telephone ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $phone = null;

    #[Assert\Date(message: 'La date de naissance doit etre au format AAAA-MM-JJ.')]
    public ?string $dateOfBirth = null;

    #[Assert\Choice(callback: [Gender::class, 'values'], message: 'Le genre selectionne est invalide.')]
    public ?string $gender = null;

    #[Assert\Length(max: 255, maxMessage: 'L\'adresse ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $address = null;

    public function getDateOfBirthAsDateTimeImmutable(): ?\DateTimeImmutable
    {
        $dateOfBirth = $this->normalizeOptionalString($this->dateOfBirth);

        if ($dateOfBirth === null) {
            return null;
        }

        $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateOfBirth);

        if (!$parsedDate instanceof \DateTimeImmutable) {
            throw new \InvalidArgumentException('La date de naissance fournie est invalide.');
        }

        return $parsedDate;
    }

    public function getGenderEnum(): ?Gender
    {
        $gender = $this->normalizeOptionalString($this->gender);

        if ($gender === null) {
            return null;
        }

        return Gender::tryFrom($gender) ?? throw new \InvalidArgumentException('Le genre fourni est invalide.');
    }

    public function getNormalizedPhone(): ?string
    {
        return $this->normalizeOptionalString($this->phone);
    }

    public function getNormalizedAddress(): ?string
    {
        return $this->normalizeOptionalString($this->address);
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
