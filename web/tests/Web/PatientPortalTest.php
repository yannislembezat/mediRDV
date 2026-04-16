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

final class PatientPortalTest extends ApiTestCase
{
    public function testPatientPagesRenderForOwnedData(): void
    {
        [$appointmentId, $consultationId] = $this->createOwnedAppointmentAndRecord();
        $this->loginPatient();

        $this->client->request('GET', '/patient');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Parcours patient actif', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/patient/recherche');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Recherche par nom et specialite', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/patient/medecin/1');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Prochains creneaux consultables', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/patient/medecin/1/rdv');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Selection du creneau et envoi de la demande', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/patient/mes-rdv');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Filtrer par statut', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', sprintf('/patient/mes-rdv/%d', $appointmentId));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Informations de traitement', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/patient/dossier-medical');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('consultation(s) completee(s)', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', sprintf('/patient/dossier-medical/%d', $consultationId));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Traitement prescrit', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/patient/profil');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Informations personnelles', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/patient/profil/modifier');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Mettre a jour mes informations', (string) $this->client->getResponse()->getContent());
    }

    public function testPatientCannotViewForeignAppointmentOrMedicalRecord(): void
    {
        [$foreignAppointmentId, $foreignConsultationId] = $this->createForeignAppointmentAndRecord();
        $this->loginPatient();

        $this->client->request('GET', sprintf('/patient/mes-rdv/%d', $foreignAppointmentId));
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', sprintf('/patient/dossier-medical/%d', $foreignConsultationId));
        self::assertResponseStatusCodeSame(404);
    }

    private function loginPatient(string $email = 'ahmed.benali@example.fr'): void
    {
        /** @var User|null $patient */
        $patient = $this->entityManager()->getRepository(User::class)->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $patient);

        $this->client->loginUser($patient, 'main');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function createOwnedAppointmentAndRecord(): array
    {
        return $this->createAppointmentAndRecord('ahmed.benali@example.fr');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function createForeignAppointmentAndRecord(): array
    {
        return $this->createAppointmentAndRecord('salma.alaoui@example.fr');
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function createAppointmentAndRecord(string $patientEmail): array
    {
        $entityManager = $this->entityManager();

        /** @var User|null $patient */
        $patient = $entityManager->getRepository(User::class)->findOneBy(['email' => $patientEmail]);
        $medecin = $entityManager->getRepository(MedecinProfile::class)->find(1);
        $medication = $entityManager->getRepository(Medication::class)->findOneBy([]);

        self::assertInstanceOf(User::class, $patient);
        self::assertInstanceOf(MedecinProfile::class, $medecin);
        self::assertInstanceOf(Medication::class, $medication);

        $appointment = (new Appointment())
            ->setPatient($patient)
            ->setMedecin($medecin)
            ->setDateTime(new \DateTimeImmutable('2026-04-20T10:00:00+00:00'))
            ->setEndTime(new \DateTimeImmutable('2026-04-20T10:30:00+00:00'))
            ->setStatus(AppointmentStatus::COMPLETED)
            ->setReason('Suivi de controle')
            ->setAdminNote('Validation admin')
            ->setValidatedAt(new \DateTimeImmutable('2026-04-19T10:00:00+00:00'));

        $consultation = (new Consultation())
            ->setAppointment($appointment)
            ->setNotes('Patient stable et suivi conseille.')
            ->setDiagnosis('Controle clinique satisfaisant')
            ->setVitalSigns([
                'blood_pressure' => '12/8',
                'heart_rate' => '72 bpm',
            ])
            ->setIsCompleted(true)
            ->setCompletedAt(new \DateTimeImmutable('2026-04-20T10:45:00+00:00'));

        $prescription = (new Prescription())
            ->setConsultation($consultation)
            ->setNotes('Traitement a suivre pendant 5 jours.');

        $prescriptionItem = (new PrescriptionItem())
            ->setPrescription($prescription)
            ->setMedication($medication)
            ->setDosage('1000mg')
            ->setDuration('5 jours')
            ->setFrequency('2 fois par jour')
            ->setInstructions('Apres le repas');

        $prescription->addItem($prescriptionItem);

        $entityManager->persist($appointment);
        $entityManager->persist($consultation);
        $entityManager->persist($prescription);
        $entityManager->persist($prescriptionItem);
        $entityManager->flush();

        return [
            (int) $appointment->getId(),
            (int) $consultation->getId(),
        ];
    }
}
