<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\AdminDoctorData;
use App\Entity\MedecinProfile;
use App\Entity\User;
use App\Enum\UserRole;
use App\Service\Exception\DuplicateUserEmailException;
use App\Service\Exception\WorkflowException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DoctorAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function create(AdminDoctorData $doctorData): MedecinProfile
    {
        $user = new User();
        $profile = new MedecinProfile();

        $this->hydrate($user, $profile, $doctorData, true);

        $this->entityManager->persist($user);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        return $profile;
    }

    public function update(MedecinProfile $profile, AdminDoctorData $doctorData): MedecinProfile
    {
        $user = $profile->getUser();

        if (!$user instanceof User) {
            throw new WorkflowException('Le profil medecin est incomplet.');
        }

        $this->hydrate($user, $profile, $doctorData, false);
        $this->entityManager->flush();

        return $profile;
    }

    public function toggle(MedecinProfile $profile): MedecinProfile
    {
        $user = $profile->getUser();

        if (!$user instanceof User) {
            throw new WorkflowException('Le compte medecin est introuvable.');
        }

        $user->setIsActive(!$user->isActive());
        $this->entityManager->flush();

        return $profile;
    }

    private function hydrate(User $user, MedecinProfile $profile, AdminDoctorData $doctorData, bool $requirePassword): void
    {
        $normalizedEmail = $doctorData->getNormalizedEmail();

        if ($normalizedEmail === '') {
            throw new \InvalidArgumentException('L\'email est obligatoire.');
        }

        $this->assertEmailIsUnique($normalizedEmail, $user->getId());

        $user
            ->setEmail($normalizedEmail)
            ->setFirstName($doctorData->firstName)
            ->setLastName($doctorData->lastName)
            ->setPhone($doctorData->getNormalizedPhone())
            ->setDateOfBirth($doctorData->getDateOfBirthAsDateTimeImmutable())
            ->setGender($doctorData->getGenderEnum())
            ->setAddress($doctorData->getNormalizedAddress())
            ->setRoles([UserRole::MEDECIN->value])
            ->setIsActive($user->getId() !== null ? $user->isActive() : true);

        $plainPassword = $doctorData->getNormalizedPassword();

        if ($plainPassword === null && $requirePassword) {
            throw new WorkflowException('Un mot de passe initial est obligatoire pour creer un medecin.');
        }

        if ($plainPassword !== null) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        }

        $profile
            ->setUser($user)
            ->setSpecialty($doctorData->specialty)
            ->setBio($doctorData->getNormalizedBio())
            ->setConsultationDuration($doctorData->consultationDuration)
            ->setOfficeLocation($doctorData->getNormalizedOfficeLocation())
            ->setYearsExperience($doctorData->yearsExperience)
            ->setDiploma($doctorData->getNormalizedDiploma());
    }

    private function assertEmailIsUnique(string $email, ?int $excludedUserId = null): void
    {
        $existingUser = $this->getUserRepository()->findOneBy(['email' => $email]);

        if ($existingUser instanceof User && $existingUser->getId() !== $excludedUserId) {
            throw DuplicateUserEmailException::forEmail($email);
        }
    }

    /**
     * @return ObjectRepository<User>
     */
    private function getUserRepository(): ObjectRepository
    {
        /** @var ObjectRepository<User> $repository */
        $repository = $this->entityManager->getRepository(User::class);

        return $repository;
    }
}
