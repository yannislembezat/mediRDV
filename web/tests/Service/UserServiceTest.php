<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\PatientRegistrationData;
use App\Entity\User;
use App\Enum\Gender;
use App\Enum\UserRole;
use App\Service\Exception\DuplicateUserEmailException;
use App\Service\UserService;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserServiceTest extends TestCase
{
    public function testItRegistersAnActivePatient(): void
    {
        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'patient@example.com'])
            ->willReturn(null);

        $passwordHasher
            ->expects(self::once())
            ->method('hashPassword')
            ->with(self::isInstanceOf(User::class), 'Motdepasse123')
            ->willReturn('hashed-password');

        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(function (User $user): bool {
                self::assertSame('patient@example.com', $user->getEmail());
                self::assertSame('Nadia', $user->getFirstName());
                self::assertSame('El Amrani', $user->getLastName());
                self::assertSame('+33600123456', $user->getPhone());
                self::assertSame('Paris', $user->getAddress());
                self::assertSame(Gender::FEMALE, $user->getGender());
                self::assertSame('1992-07-16', $user->getDateOfBirth()?->format('Y-m-d'));
                self::assertTrue($user->isActive());
                self::assertSame([UserRole::PATIENT->value], $user->getRoles());
                self::assertSame('hashed-password', $user->getPassword());

                return true;
            }));

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $registrationData = new PatientRegistrationData();
        $registrationData->email = 'Patient@example.com';
        $registrationData->plainPassword = 'Motdepasse123';
        $registrationData->firstName = 'Nadia';
        $registrationData->lastName = 'El Amrani';
        $registrationData->phone = '+33600123456';
        $registrationData->address = 'Paris';
        $registrationData->gender = Gender::FEMALE->value;
        $registrationData->dateOfBirth = '1992-07-16';

        $service = new UserService($entityManager, $passwordHasher);
        $registeredUser = $service->registerPatient($registrationData);

        self::assertSame('patient@example.com', $registeredUser->getEmail());
    }

    public function testItRejectsDuplicateEmails(): void
    {
        $repository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'patient@example.com'])
            ->willReturn(new User());

        $entityManager
            ->expects(self::never())
            ->method('persist');

        $entityManager
            ->expects(self::never())
            ->method('flush');

        $passwordHasher
            ->expects(self::never())
            ->method('hashPassword');

        $registrationData = new PatientRegistrationData();
        $registrationData->email = 'patient@example.com';
        $registrationData->plainPassword = 'Motdepasse123';
        $registrationData->firstName = 'Nadia';
        $registrationData->lastName = 'El Amrani';

        $service = new UserService($entityManager, $passwordHasher);

        $this->expectException(DuplicateUserEmailException::class);
        $service->registerPatient($registrationData);
    }
}
