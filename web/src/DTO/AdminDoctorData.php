<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Specialty;
use App\Enum\Gender;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class AdminDoctorData
{
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'Veuillez saisir une adresse email valide.')]
    #[Assert\Length(max: 180, maxMessage: 'L\'email ne peut pas depasser {{ limit }} caracteres.')]
    public string $email = '';

    public ?string $plainPassword = null;

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

    #[Assert\NotNull(message: 'La specialite est obligatoire.')]
    public ?Specialty $specialty = null;

    #[Assert\Length(max: 10000, maxMessage: 'La biographie ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $bio = null;

    #[Assert\NotBlank(message: 'La duree de consultation est obligatoire.')]
    #[Assert\Range(
        min: 10,
        max: 180,
        notInRangeMessage: 'La duree de consultation doit etre comprise entre {{ min }} et {{ max }} minutes.',
    )]
    public int $consultationDuration = 30;

    #[Assert\Length(max: 255, maxMessage: 'Le cabinet ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $officeLocation = null;

    #[Assert\Range(
        min: 0,
        max: 80,
        notInRangeMessage: 'L\'experience doit etre comprise entre {{ min }} et {{ max }} ans.',
    )]
    public ?int $yearsExperience = null;

    #[Assert\Length(max: 255, maxMessage: 'Le diplome ne peut pas depasser {{ limit }} caracteres.')]
    public ?string $diploma = null;

    public function getNormalizedEmail(): string
    {
        return mb_strtolower(trim($this->email));
    }

    public function getNormalizedPassword(): ?string
    {
        return $this->normalizeOptionalString($this->plainPassword);
    }

    public function getNormalizedPhone(): ?string
    {
        return $this->normalizeOptionalString($this->phone);
    }

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

    public function getNormalizedAddress(): ?string
    {
        return $this->normalizeOptionalString($this->address);
    }

    public function getNormalizedBio(): ?string
    {
        return $this->normalizeOptionalString($this->bio);
    }

    public function getNormalizedOfficeLocation(): ?string
    {
        return $this->normalizeOptionalString($this->officeLocation);
    }

    public function getNormalizedDiploma(): ?string
    {
        return $this->normalizeOptionalString($this->diploma);
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $password = $this->getNormalizedPassword();

        if ($password === null) {
            return;
        }

        $length = mb_strlen($password);

        if ($length < 8) {
            $context->buildViolation('Le mot de passe doit contenir au moins 8 caracteres.')
                ->atPath('plainPassword')
                ->addViolation();
        }

        if ($length > 255) {
            $context->buildViolation('Le mot de passe ne peut pas depasser 255 caracteres.')
                ->atPath('plainPassword')
                ->addViolation();
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
