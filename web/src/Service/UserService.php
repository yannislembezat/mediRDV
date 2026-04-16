<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\PatientProfileUpdateData;
use App\DTO\PatientRegistrationData;
use App\Entity\User;
use App\Enum\UserRole;
use App\Service\Exception\DuplicateUserEmailException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function registerPatient(PatientRegistrationData $registrationData): User
    {
        $normalizedEmail = mb_strtolower(trim($registrationData->email));

        if ($normalizedEmail === '') {
            throw new \InvalidArgumentException('L\'email est obligatoire.');
        }

        if ($this->getUserRepository()->findOneBy(['email' => $normalizedEmail]) instanceof User) {
            throw DuplicateUserEmailException::forEmail($normalizedEmail);
        }

        $user = (new User())
            ->setEmail($normalizedEmail)
            ->setFirstName($registrationData->firstName)
            ->setLastName($registrationData->lastName)
            ->setPhone($registrationData->getNormalizedPhone())
            ->setDateOfBirth($registrationData->getDateOfBirthAsDateTimeImmutable())
            ->setGender($registrationData->getGenderEnum())
            ->setAddress($registrationData->getNormalizedAddress())
            ->setRoles([UserRole::PATIENT->value])
            ->setIsActive(true);

        $user->setPassword($this->passwordHasher->hashPassword($user, $registrationData->plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateProfile(User $user, PatientProfileUpdateData $profileData): User
    {
        $normalizedEmail = mb_strtolower(trim($profileData->email));

        if ($normalizedEmail === '') {
            throw new \InvalidArgumentException('L\'email est obligatoire.');
        }

        $existingUser = $this->getUserRepository()->findOneBy(['email' => $normalizedEmail]);

        if ($existingUser instanceof User && $existingUser->getId() !== $user->getId()) {
            throw DuplicateUserEmailException::forEmail($normalizedEmail);
        }

        $user
            ->setEmail($normalizedEmail)
            ->setFirstName($profileData->firstName)
            ->setLastName($profileData->lastName)
            ->setPhone($profileData->getNormalizedPhone())
            ->setDateOfBirth($profileData->getDateOfBirthAsDateTimeImmutable())
            ->setGender($profileData->getGenderEnum())
            ->setAddress($profileData->getNormalizedAddress());

        $this->entityManager->flush();

        return $user;
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
