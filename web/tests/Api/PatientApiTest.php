<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Appointment;
use App\Entity\Consultation;
use App\Entity\Notification;
use App\Entity\Prescription;
use App\Entity\PrescriptionItem;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Enum\NotificationType;
use Symfony\Component\HttpFoundation\Response;

final class PatientApiTest extends ApiTestCase
{
    public function testAuthenticationEndpointsAndUnauthorizedPayload(): void
    {
        $unauthorizedPayload = $this->jsonRequest('GET', '/api/me');
        $this->assertStatusCode(Response::HTTP_UNAUTHORIZED);
        self::assertSame(401, $unauthorizedPayload['code']);
        self::assertSame('Authentification requise.', $unauthorizedPayload['message']);

        $failedLoginPayload = $this->jsonRequest('POST', '/api/login', [
            'email' => 'ahmed.benali@example.fr',
            'password' => 'wrong-password',
        ]);
        $this->assertStatusCode(Response::HTTP_UNAUTHORIZED);
        self::assertSame(401, $failedLoginPayload['code']);
        self::assertSame('Email ou mot de passe invalide.', $failedLoginPayload['message']);

        $loginPayload = $this->jsonRequest('POST', '/api/login', [
            'email' => 'ahmed.benali@example.fr',
            'password' => 'patient123',
        ]);
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertArrayHasKey('token', $loginPayload);
        self::assertArrayHasKey('refreshToken', $loginPayload);
        self::assertArrayHasKey('refreshTokenExpiration', $loginPayload);

        $refreshPayload = $this->jsonRequest('POST', '/api/token/refresh', [
            'refreshToken' => $loginPayload['refreshToken'],
        ]);
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertArrayHasKey('token', $refreshPayload);
        self::assertArrayHasKey('refreshToken', $refreshPayload);
        self::assertArrayHasKey('refreshTokenExpiration', $refreshPayload);
    }

    public function testProfileCatalogAndSlotsEndpoints(): void
    {
        $token = $this->authenticatePatient();

        $profilePayload = $this->jsonRequest('GET', '/api/me', [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertSame('ahmed.benali@example.fr', $profilePayload['data']['email']);

        $updatedProfilePayload = $this->jsonRequest('PUT', '/api/me', [
            'email' => 'ahmed.updated@example.com',
            'firstName' => 'Ahmed',
            'lastName' => 'Benali',
            'phone' => '+33612345678',
            'dateOfBirth' => '1990-05-15',
            'gender' => 'M',
            'address' => 'Paris - 15ème',
        ], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertSame('Profil mis a jour avec succes.', $updatedProfilePayload['message']);
        self::assertSame('ahmed.updated@example.com', $updatedProfilePayload['data']['email']);
        self::assertSame('Paris - 15ème', $updatedProfilePayload['data']['address']);
        $token = $this->authenticatePatient('ahmed.updated@example.com');

        $specialtiesPayload = $this->jsonRequest('GET', '/api/specialties', [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertGreaterThanOrEqual(10, $specialtiesPayload['total']);

        $medecinsPayload = $this->jsonRequest('GET', '/api/medecins?search=fatima&specialtyId=2', [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertSame(1, $medecinsPayload['total']);
        self::assertSame('Dr. Fatima Zohra', $medecinsPayload['data'][0]['fullName']);

        $medecinPayload = $this->jsonRequest('GET', '/api/medecins/1', [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertSame('Dr. Fatima Zohra', $medecinPayload['data']['fullName']);
        self::assertNotEmpty($medecinPayload['data']['availabilities']);

        $slotsPayload = $this->jsonRequest('GET', '/api/medecins/1/slots?date=2026-04-20', [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertGreaterThanOrEqual(1, $slotsPayload['total']);
        self::assertSame('2026-04-20', $slotsPayload['date']);
        self::assertContains(
            '2026-04-20T10:00:00+00:00',
            array_column($slotsPayload['data'], 'dateTime'),
        );
    }

    public function testPatientCanCreateListShowAndCancelAppointments(): void
    {
        $token = $this->authenticatePatient();

        $createdPayload = $this->jsonRequest('POST', '/api/appointments', [
            'medecinId' => 1,
            'dateTime' => '2026-04-20T10:00:00+00:00',
            'reason' => 'Controle cardiologique',
        ], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_CREATED);
        self::assertSame('Rendez-vous demande avec succes.', $createdPayload['message']);
        self::assertSame('pending', $createdPayload['data']['status']);

        $appointmentId = $createdPayload['data']['id'];

        $listPayload = $this->jsonRequest('GET', '/api/appointments?status=pending&page=1&limit=10', [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertSame(1, $listPayload['total']);
        self::assertSame($appointmentId, $listPayload['data'][0]['id']);

        $detailPayload = $this->jsonRequest('GET', sprintf('/api/appointments/%d', $appointmentId), [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertSame('Controle cardiologique', $detailPayload['data']['reason']);

        $cancelPayload = $this->jsonRequest('DELETE', sprintf('/api/appointments/%d', $appointmentId), [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertSame('cancelled', $cancelPayload['data']['status']);
    }

    public function testMedicalRecordsAndNotificationsEndpoints(): void
    {
        $consultationId = $this->createCompletedConsultation();
        $token = $this->authenticatePatient();

        $medicalRecordsPayload = $this->jsonRequest('GET', '/api/medical-records', [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertSame(1, $medicalRecordsPayload['total']);
        self::assertSame($consultationId, $medicalRecordsPayload['data'][0]['id']);

        $medicalRecordPayload = $this->jsonRequest('GET', sprintf('/api/medical-records/%d', $consultationId), [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertSame('Bilan cardiologique stable', $medicalRecordPayload['data']['diagnosis']);
        self::assertCount(1, $medicalRecordPayload['data']['prescription']['items']);

        $notificationsPayload = $this->jsonRequest('GET', '/api/notifications', [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertGreaterThanOrEqual(2, $notificationsPayload['unreadCount']);
        self::assertNotEmpty($notificationsPayload['data']);

        $notificationId = $notificationsPayload['data'][0]['id'];
        $markReadPayload = $this->jsonRequest('PATCH', sprintf('/api/notifications/%d/read', $notificationId), [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertTrue($markReadPayload['data']['isRead']);

        $freshNotificationsPayload = $this->jsonRequest('GET', '/api/notifications', [], $this->authHeaders($token));
        $this->assertStatusCode(Response::HTTP_OK);
        self::assertGreaterThanOrEqual(1, $freshNotificationsPayload['unreadCount']);
    }

    private function createCompletedConsultation(): int
    {
        $entityManager = $this->entityManager();

        /** @var User $patient */
        $patient = $entityManager->getRepository(User::class)->findOneBy(['email' => 'ahmed.benali@example.fr']);
        /** @var User $admin */
        $admin = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@medirdv.fr']);
        $medecin = $entityManager->getRepository(\App\Entity\MedecinProfile::class)->find(1);
        $medication = $entityManager->getRepository(\App\Entity\Medication::class)->findOneBy([]);

        self::assertInstanceOf(User::class, $patient);
        self::assertInstanceOf(User::class, $admin);
        self::assertNotNull($medecin);
        self::assertNotNull($medication);
        self::assertInstanceOf(User::class, $medecin->getUser());

        $appointment = (new Appointment())
            ->setPatient($patient)
            ->setMedecin($medecin)
            ->setDateTime(new \DateTimeImmutable('2026-04-20T10:00:00+00:00'))
            ->setEndTime(new \DateTimeImmutable('2026-04-20T10:30:00+00:00'))
            ->setStatus(AppointmentStatus::COMPLETED)
            ->setReason('Suivi cardiologique annuel')
            ->setAdminNote('Valide pour consultation')
            ->setValidatedBy($admin)
            ->setValidatedAt(new \DateTimeImmutable('2026-04-19T09:00:00+00:00'));

        $consultation = (new Consultation())
            ->setAppointment($appointment)
            ->setNotes('Patient stable, aucune complication.')
            ->setDiagnosis('Bilan cardiologique stable')
            ->setVitalSigns([
                'bloodPressure' => '12/8',
                'heartRate' => '72 bpm',
            ])
            ->setIsCompleted(true)
            ->setCompletedAt(new \DateTimeImmutable('2026-04-20T10:45:00+00:00'));

        $prescription = (new Prescription())
            ->setConsultation($consultation)
            ->setNotes('Traitement court');

        $prescriptionItem = (new PrescriptionItem())
            ->setPrescription($prescription)
            ->setMedication($medication)
            ->setDosage('1000mg')
            ->setDuration('5 jours')
            ->setFrequency('2 fois par jour')
            ->setInstructions('Apres le repas')
            ->setDisplayOrder(0);

        $prescription->addItem($prescriptionItem);

        $notificationConfirmed = (new Notification())
            ->setUser($patient)
            ->setType(NotificationType::APPOINTMENT_CONFIRMED)
            ->setTitle('Rendez-vous confirmé')
            ->setMessage('Votre rendez-vous du 20/04/2026 avec Dr. Fatima Zohra a ete confirme.')
            ->setReferenceId(1);

        $notificationConsultation = (new Notification())
            ->setUser($patient)
            ->setType(NotificationType::CONSULTATION_READY)
            ->setTitle('Consultation disponible')
            ->setMessage('Votre consultation du 20/04/2026 est disponible dans votre dossier medical.')
            ->setReferenceId(1);

        $entityManager->persist($appointment);
        $entityManager->persist($consultation);
        $entityManager->persist($prescription);
        $entityManager->persist($prescriptionItem);
        $entityManager->persist($notificationConfirmed);
        $entityManager->persist($notificationConsultation);
        $entityManager->flush();

        return (int) $consultation->getId();
    }
}
