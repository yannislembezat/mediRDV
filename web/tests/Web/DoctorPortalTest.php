<?php

declare(strict_types=1);

namespace App\Tests\Web;

use App\Entity\Appointment;
use App\Entity\Consultation;
use App\Entity\MedecinProfile;
use App\Entity\Medication;
use App\Entity\Prescription;
use App\Entity\PrescriptionItem;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Tests\Api\ApiTestCase;

final class DoctorPortalTest extends ApiTestCase
{
    public function testDoctorPagesRenderForOwnedData(): void
    {
        $data = $this->createOwnedDoctorData();
        $this->loginDoctor();

        $this->client->request('GET', '/medecin?view=week');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Agenda medecin jour / semaine', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/medecin?view=day&date=2026-04-20');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Charge du jour cible', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', sprintf('/medecin/rdv/%d', $data['draftAppointmentId']));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Informations cliniques et administratives', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', sprintf('/medecin/rdv/%d/consultation', $data['draftAppointmentId']));
        self::assertResponseRedirects(sprintf('/medecin/consultation/%d/ordonnance', $data['draftConsultationId']));
        $this->client->followRedirect();
        self::assertStringContainsString('2 lignes de traitement', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', sprintf('/medecin/consultation/%d/ordonnance', $data['draftConsultationId']));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Lignes de traitement', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', sprintf('/medecin/consultation/%d', $data['completedConsultationId']));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Traitement associe', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', sprintf('/medecin/patient/%d', $data['patientId']));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Consultations completes du patient', (string) $this->client->getResponse()->getContent());
    }

    public function testDoctorCannotViewForeignAppointmentConsultationOrPatientRecord(): void
    {
        $foreignData = $this->createForeignDoctorData();
        $this->loginDoctor();

        $this->client->request('GET', sprintf('/medecin/rdv/%d', $foreignData['appointmentId']));
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', sprintf('/medecin/consultation/%d', $foreignData['consultationId']));
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', sprintf('/medecin/consultation/%d/ordonnance', $foreignData['consultationId']));
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', sprintf('/medecin/patient/%d', $foreignData['patientId']));
        self::assertResponseStatusCodeSame(404);
    }

    public function testDoctorCanFinalizeConsultationAndCompleteAppointment(): void
    {
        $appointmentId = $this->createFinalizeFlowAppointment();
        $this->loginDoctor();

        $this->client->request('GET', sprintf('/medecin/rdv/%d/consultation', $appointmentId));
        self::assertResponseRedirects();
        $this->client->followRedirect();

        $this->client->submitForm('Finaliser la consultation');

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertStringContainsString('Consultation finalisee', (string) $this->client->getResponse()->getContent());

        $this->entityManager()->clear();

        /** @var Appointment|null $appointment */
        $appointment = $this->entityManager()->getRepository(Appointment::class)->find($appointmentId);

        self::assertInstanceOf(Appointment::class, $appointment);
        self::assertSame(AppointmentStatus::COMPLETED, $appointment->getStatus());
        self::assertTrue($appointment->getConsultation()?->isCompleted());
        self::assertNotNull($appointment->getConsultation()?->getCompletedAt());
    }

    private function loginDoctor(string $email = 'fatima.zohra@medirdv.fr'): void
    {
        /** @var User|null $doctor */
        $doctor = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $doctor);

        $this->client->loginUser($doctor, 'main');
    }

    /**
     * @return array{
     *     draftAppointmentId: int,
     *     draftConsultationId: int,
     *     completedConsultationId: int,
     *     patientId: int
     * }
     */
    private function createOwnedDoctorData(): array
    {
        $entityManager = $this->entityManager();
        $doctor = $this->findDoctorProfile('fatima.zohra@medirdv.fr');

        /** @var User|null $patient */
        $patient = $entityManager->getRepository(User::class)->findOneBy(['email' => 'ahmed.benali@example.fr']);
        /** @var Medication|null $medication */
        $medication = $entityManager->getRepository(Medication::class)->findOneBy([]);

        self::assertInstanceOf(User::class, $patient);
        self::assertInstanceOf(Medication::class, $medication);

        $draftAppointment = (new Appointment())
            ->setPatient($patient)
            ->setMedecin($doctor)
            ->setDateTime(new \DateTimeImmutable('2026-04-20T09:00:00+00:00'))
            ->setEndTime(new \DateTimeImmutable('2026-04-20T09:30:00+00:00'))
            ->setStatus(AppointmentStatus::CONFIRMED)
            ->setReason('Suivi cardiologique')
            ->setAdminNote('Valide par l administration');

        $draftConsultation = (new Consultation())
            ->setAppointment($draftAppointment)
            ->setNotes('Brouillon en cours.')
            ->setDiagnosis('Observation initiale')
            ->setVitalSigns([
                'blood_pressure' => '13/8',
                'heart_rate' => '80 bpm',
            ]);

        $completedAppointment = (new Appointment())
            ->setPatient($patient)
            ->setMedecin($doctor)
            ->setDateTime(new \DateTimeImmutable('2026-04-18T10:00:00+00:00'))
            ->setEndTime(new \DateTimeImmutable('2026-04-18T10:30:00+00:00'))
            ->setStatus(AppointmentStatus::COMPLETED)
            ->setReason('Controle post traitement')
            ->setAdminNote('Historique de suivi');

        $completedConsultation = (new Consultation())
            ->setAppointment($completedAppointment)
            ->setNotes('Patient en amelioration.')
            ->setDiagnosis('Evolution favorable')
            ->setVitalSigns([
                'blood_pressure' => '12/8',
                'temperature' => '36.7 C',
            ])
            ->setIsCompleted(true)
            ->setCompletedAt(new \DateTimeImmutable('2026-04-18T10:40:00+00:00'));

        $prescription = (new Prescription())
            ->setConsultation($completedConsultation)
            ->setNotes('Poursuivre le traitement pendant 5 jours.');

        $prescriptionItem = (new PrescriptionItem())
            ->setPrescription($prescription)
            ->setMedication($medication)
            ->setDosage('1 comprime matin et soir')
            ->setDuration('5 jours')
            ->setFrequency('2 fois par jour')
            ->setInstructions('Apres le repas')
            ->setDisplayOrder(0);

        $prescription->addItem($prescriptionItem);

        $entityManager->persist($draftAppointment);
        $entityManager->persist($draftConsultation);
        $entityManager->persist($completedAppointment);
        $entityManager->persist($completedConsultation);
        $entityManager->persist($prescription);
        $entityManager->persist($prescriptionItem);
        $entityManager->flush();

        return [
            'draftAppointmentId' => (int) $draftAppointment->getId(),
            'draftConsultationId' => (int) $draftConsultation->getId(),
            'completedConsultationId' => (int) $completedConsultation->getId(),
            'patientId' => (int) $patient->getId(),
        ];
    }

    /**
     * @return array{
     *     appointmentId: int,
     *     consultationId: int,
     *     patientId: int
     * }
     */
    private function createForeignDoctorData(): array
    {
        $entityManager = $this->entityManager();
        $doctor = $this->findDoctorProfile('youssef.bennani@medirdv.fr');

        /** @var User|null $patient */
        $patient = $entityManager->getRepository(User::class)->findOneBy(['email' => 'salma.alaoui@example.fr']);

        self::assertInstanceOf(User::class, $patient);

        $appointment = (new Appointment())
            ->setPatient($patient)
            ->setMedecin($doctor)
            ->setDateTime(new \DateTimeImmutable('2026-04-21T11:00:00+00:00'))
            ->setEndTime(new \DateTimeImmutable('2026-04-21T11:20:00+00:00'))
            ->setStatus(AppointmentStatus::COMPLETED)
            ->setReason('Suivi de medecine generale')
            ->setAdminNote('Reservation validee');

        $consultation = (new Consultation())
            ->setAppointment($appointment)
            ->setNotes('Consultation d un autre praticien.')
            ->setDiagnosis('Suivi general')
            ->setIsCompleted(true)
            ->setCompletedAt(new \DateTimeImmutable('2026-04-21T11:30:00+00:00'));

        $entityManager->persist($appointment);
        $entityManager->persist($consultation);
        $entityManager->flush();

        return [
            'appointmentId' => (int) $appointment->getId(),
            'consultationId' => (int) $consultation->getId(),
            'patientId' => (int) $patient->getId(),
        ];
    }

    private function createFinalizeFlowAppointment(): int
    {
        $entityManager = $this->entityManager();
        $doctor = $this->findDoctorProfile('fatima.zohra@medirdv.fr');

        /** @var User|null $patient */
        $patient = $entityManager->getRepository(User::class)->findOneBy(['email' => 'salma.alaoui@example.fr']);

        self::assertInstanceOf(User::class, $patient);

        $appointment = (new Appointment())
            ->setPatient($patient)
            ->setMedecin($doctor)
            ->setDateTime(new \DateTimeImmutable('2026-04-22T09:30:00+00:00'))
            ->setEndTime(new \DateTimeImmutable('2026-04-22T10:00:00+00:00'))
            ->setStatus(AppointmentStatus::CONFIRMED)
            ->setReason('Consultation de suivi')
            ->setAdminNote('Validation admin');

        $entityManager->persist($appointment);
        $entityManager->flush();

        return (int) $appointment->getId();
    }

    private function findDoctorProfile(string $email): MedecinProfile
    {
        /** @var User|null $doctor */
        $doctor = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $doctor);
        self::assertInstanceOf(MedecinProfile::class, $doctor->getMedecinProfile());

        return $doctor->getMedecinProfile();
    }
}
