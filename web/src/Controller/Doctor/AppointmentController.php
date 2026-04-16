<?php

declare(strict_types=1);

namespace App\Controller\Doctor;

use App\Entity\MedecinProfile;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\ConsultationRepository;
use App\Service\Web\DoctorPortalContextBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MEDECIN')]
final class AppointmentController extends AbstractController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly ConsultationRepository $consultationRepository,
        private readonly DoctorPortalContextBuilder $doctorPortalContextBuilder,
    ) {
    }

    #[Route('/medecin/rdv/{id}', name: 'doctor_appointment_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, #[CurrentUser] User $doctor): Response
    {
        $medecinProfile = $this->requireMedecinProfile($doctor);
        $appointment = $this->appointmentRepository->findOneForMedecinById($medecinProfile, $id);

        if ($appointment === null) {
            throw $this->createNotFoundException('Le rendez-vous demande est introuvable.');
        }

        $patient = $appointment->getPatient();
        $patientHistory = $patient instanceof User
            ? $this->appointmentRepository->findHistoryForMedecinPatient($medecinProfile, $patient)
            : [];
        $patientRecords = $patient instanceof User
            ? array_slice($this->consultationRepository->findCompletedForPatient($patient), 0, 3)
            : [];

        return $this->render('doctor/appointments/detail.html.twig', array_merge([
            'doctorProfile' => $medecinProfile,
            'appointment' => $appointment,
            'patientHistory' => $patientHistory,
            'patientRecords' => $patientRecords,
        ], $this->doctorPortalContextBuilder->build($doctor)));
    }

    private function requireMedecinProfile(User $doctor): MedecinProfile
    {
        $medecinProfile = $doctor->getMedecinProfile();

        if (!$medecinProfile instanceof MedecinProfile) {
            throw $this->createAccessDeniedException('Le compte medecin courant ne possede pas de profil praticien.');
        }

        return $medecinProfile;
    }
}
