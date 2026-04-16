<?php

declare(strict_types=1);

namespace App\Tests\Web;

use App\Entity\Appointment;
use App\Entity\Availability;
use App\Entity\MedecinProfile;
use App\Entity\Specialty;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Tests\Api\ApiTestCase;

final class AdminPortalTest extends ApiTestCase
{
    public function testAdminPagesRender(): void
    {
        $appointment = $this->createPendingAppointment();
        $this->loginAdmin();

        $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('KPIs operationnels', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/rendez-vous');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Date, statut et medecin', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', sprintf('/admin/rendez-vous/%d', $appointment->getId()));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Decision administrative', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/medecins');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Recherche medecin', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/medecins/nouveau');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Creer le medecin', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/medecins/1');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Indicateurs du praticien', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/medecins/1/modifier');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Mettre a jour la fiche', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/disponibilites');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Choisir un medecin', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/admin/disponibilites/ajouter');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Ajouter un creneau', (string) $this->client->getResponse()->getContent());
    }

    public function testAdminCanCreateEditAndToggleDoctor(): void
    {
        $this->loginAdmin();

        /** @var Specialty $specialty */
        $specialty = $this->entityManager()->getRepository(Specialty::class)->findOneBy([]);
        self::assertInstanceOf(Specialty::class, $specialty);

        $this->client->request('GET', '/admin/medecins/nouveau');
        $this->client->submitForm('Creer le medecin', [
            'admin_doctor[firstName]' => 'Samir',
            'admin_doctor[lastName]' => 'Mansouri',
            'admin_doctor[email]' => 'samir.mansouri@medirdv.fr',
            'admin_doctor[plainPassword][first]' => 'Doctorpass123',
            'admin_doctor[plainPassword][second]' => 'Doctorpass123',
            'admin_doctor[phone]' => '+33699887766',
            'admin_doctor[dateOfBirth]' => '1984-08-12',
            'admin_doctor[gender]' => 'M',
            'admin_doctor[address]' => 'Paris - 15ème',
            'admin_doctor[specialty]' => (string) $specialty->getId(),
            'admin_doctor[officeLocation]' => 'Bloc C - Cabinet 12',
            'admin_doctor[consultationDuration]' => '35',
            'admin_doctor[yearsExperience]' => '14',
            'admin_doctor[diploma]' => 'DES de specialite',
            'admin_doctor[bio]' => 'Suivi expert des patients en consultation.',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();

        /** @var User|null $createdUser */
        $createdUser = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => 'samir.mansouri@medirdv.fr']);
        self::assertInstanceOf(User::class, $createdUser);
        self::assertTrue($createdUser->hasRole(\App\Enum\UserRole::MEDECIN));
        self::assertTrue($createdUser->isActive());

        $doctor = $createdUser->getMedecinProfile();
        self::assertInstanceOf(MedecinProfile::class, $doctor);

        $this->client->request('GET', sprintf('/admin/medecins/%d/modifier', $doctor->getId()));
        $this->client->submitForm('Mettre a jour le medecin', [
            'admin_doctor[firstName]' => 'Samir',
            'admin_doctor[lastName]' => 'Mansouri',
            'admin_doctor[email]' => 'samir.mansouri@medirdv.fr',
            'admin_doctor[plainPassword][first]' => '',
            'admin_doctor[plainPassword][second]' => '',
            'admin_doctor[phone]' => '+33699887766',
            'admin_doctor[dateOfBirth]' => '1984-08-12',
            'admin_doctor[gender]' => 'M',
            'admin_doctor[address]' => 'Paris - 15ème',
            'admin_doctor[specialty]' => (string) $specialty->getId(),
            'admin_doctor[officeLocation]' => 'Bloc D - Cabinet 18',
            'admin_doctor[consultationDuration]' => '40',
            'admin_doctor[yearsExperience]' => '15',
            'admin_doctor[diploma]' => 'DES de specialite',
            'admin_doctor[bio]' => 'Suivi expert et organisation revue.',
        ]);

        self::assertResponseRedirects();

        $this->client->request('GET', sprintf('/admin/medecins/%d', $doctor->getId()));
        $toggleToken = $this->client->getCrawler()
            ->filter(sprintf('form[action="/admin/medecins/%d/toggle"] input[name="_token"]', $doctor->getId()))
            ->attr('value');

        self::assertIsString($toggleToken);

        $this->client->request('POST', sprintf('/admin/medecins/%d/toggle', $doctor->getId()), [
            '_token' => $toggleToken,
        ]);

        self::assertResponseRedirects();

        $this->entityManager()->clear();

        /** @var User|null $updatedUser */
        $updatedUser = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => 'samir.mansouri@medirdv.fr']);
        self::assertInstanceOf(User::class, $updatedUser);
        self::assertFalse($updatedUser->isActive());
        self::assertSame('Bloc D - Cabinet 18', $updatedUser->getMedecinProfile()?->getOfficeLocation());
        self::assertSame(40, $updatedUser->getMedecinProfile()?->getConsultationDuration());
    }

    public function testAdminCanAddAndDeleteAvailability(): void
    {
        $this->loginAdmin();

        $this->client->request('GET', '/admin/disponibilites/ajouter');
        $this->client->submitForm('Enregistrer le creneau', [
            'admin_availability[medecin]' => '1',
            'admin_availability[mode]' => 'specific',
            'admin_availability[dayOfWeek]' => '',
            'admin_availability[specificDate]' => '2026-05-10',
            'admin_availability[startTime]' => '10:00',
            'admin_availability[endTime]' => '12:00',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();

        /** @var MedecinProfile|null $doctor */
        $doctor = $this->entityManager()->getRepository(MedecinProfile::class)->find(1);
        self::assertInstanceOf(MedecinProfile::class, $doctor);

        $availability = $this->findSpecificAvailability($doctor, '2026-05-10', '10:00');
        self::assertInstanceOf(Availability::class, $availability);

        $deleteToken = $this->client->getCrawler()
            ->filter(sprintf('form[action="/admin/disponibilites/%d/supprimer"] input[name="_token"]', $availability->getId()))
            ->attr('value');

        self::assertIsString($deleteToken);

        $this->client->request('POST', sprintf('/admin/disponibilites/%d/supprimer', $availability->getId()), [
            '_token' => $deleteToken,
        ]);

        self::assertResponseRedirects();

        $this->entityManager()->clear();
        self::assertNull($this->entityManager()->getRepository(Availability::class)->find($availability->getId()));
    }

    public function testAdminApprovalIsBlockedWhenSlotIsAlreadyOccupied(): void
    {
        $targetAppointment = $this->createPendingAppointment('ahmed.benali@example.fr');
        $this->createConflictingConfirmedAppointment('salma.alaoui@example.fr', $targetAppointment->getDateTime());
        $this->loginAdmin();

        $this->client->request('GET', sprintf('/admin/rendez-vous/%d', $targetAppointment->getId()));
        $approveToken = $this->client->getCrawler()
            ->filter(sprintf('form[action="/admin/rendez-vous/%d/approuver"] input[name="_token"]', $targetAppointment->getId()))
            ->attr('value');

        self::assertIsString($approveToken);

        $this->client->request('POST', sprintf('/admin/rendez-vous/%d/approuver', $targetAppointment->getId()), [
            '_token' => $approveToken,
            'admin_note' => 'Validation apres controle',
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        $this->entityManager()->clear();

        /** @var Appointment|null $reloadedAppointment */
        $reloadedAppointment = $this->entityManager()->getRepository(Appointment::class)->find($targetAppointment->getId());
        self::assertInstanceOf(Appointment::class, $reloadedAppointment);
        self::assertSame(AppointmentStatus::PENDING, $reloadedAppointment->getStatus());
        self::assertStringContainsString('Decision administrative', (string) $this->client->getResponse()->getContent());
    }

    private function loginAdmin(string $email = 'admin@medirdv.fr'): void
    {
        /** @var User|null $admin */
        $admin = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $admin);

        $this->client->loginUser($admin, 'main');
    }

    private function createPendingAppointment(string $patientEmail = 'ahmed.benali@example.fr'): Appointment
    {
        return $this->createAppointment($patientEmail, AppointmentStatus::PENDING, new \DateTimeImmutable('2026-04-20T10:00:00+00:00'));
    }

    private function createConflictingConfirmedAppointment(string $patientEmail, \DateTimeImmutable $startsAt): Appointment
    {
        return $this->createAppointment($patientEmail, AppointmentStatus::CONFIRMED, $startsAt);
    }

    private function createAppointment(string $patientEmail, AppointmentStatus $status, \DateTimeImmutable $startsAt): Appointment
    {
        $entityManager = $this->entityManager();

        /** @var User|null $patient */
        $patient = $entityManager->getRepository(User::class)->findOneBy(['email' => $patientEmail]);
        /** @var User|null $admin */
        $admin = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@medirdv.fr']);
        /** @var MedecinProfile|null $doctor */
        $doctor = $entityManager->getRepository(MedecinProfile::class)->find(1);

        self::assertInstanceOf(User::class, $patient);
        self::assertInstanceOf(User::class, $admin);
        self::assertInstanceOf(MedecinProfile::class, $doctor);

        $appointment = (new Appointment())
            ->setPatient($patient)
            ->setMedecin($doctor)
            ->setDateTime($startsAt)
            ->setEndTime($startsAt->modify('+30 minutes'))
            ->setStatus($status)
            ->setReason('Controle administratif')
            ->setAdminNote(null);

        if ($status === AppointmentStatus::CONFIRMED) {
            $appointment
                ->setValidatedBy($admin)
                ->setValidatedAt($startsAt->modify('-1 day'));
        }

        $entityManager->persist($appointment);
        $entityManager->flush();

        return $appointment;
    }

    private function findSpecificAvailability(MedecinProfile $doctor, string $date, string $startTime): ?Availability
    {
        foreach ($this->entityManager()->getRepository(Availability::class)->findBy(['medecin' => $doctor]) as $availability) {
            if (
                $availability instanceof Availability
                && !$availability->isRecurring()
                && $availability->getSpecificDate()?->format('Y-m-d') === $date
                && $availability->getStartTime()->format('H:i') === $startTime
            ) {
                return $availability;
            }
        }

        return null;
    }
}
