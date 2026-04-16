<?php

declare(strict_types=1);

namespace App\Controller\Doctor;

use App\Entity\MedecinProfile;
use App\Entity\User;
use App\Enum\UserRole;
use App\Repository\AppointmentRepository;
use App\Repository\ConsultationRepository;
use App\Repository\UserRepository;
use App\Service\Web\DoctorPortalContextBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MEDECIN')]
final class PatientController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AppointmentRepository $appointmentRepository,
        private readonly ConsultationRepository $consultationRepository,
        private readonly DoctorPortalContextBuilder $doctorPortalContextBuilder,
    ) {
    }

    #[Route('/medecin/patient/{id}', name: 'doctor_patient_record', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, #[CurrentUser] User $doctor): Response
    {
        $medecinProfile = $this->requireMedecinProfile($doctor);
        $patient = $this->userRepository->find($id);

        if (!$patient instanceof User || !$patient->hasRole(UserRole::PATIENT)) {
            throw $this->createNotFoundException('Le patient demande est introuvable.');
        }

        if (!$this->appointmentRepository->hasRelationshipWithPatient($medecinProfile, $patient)) {
            throw $this->createNotFoundException('Le patient demande est introuvable.');
        }

        return $this->render('doctor/patients/record.html.twig', array_merge([
            'doctorProfile' => $medecinProfile,
            'patient' => $patient,
            'doctorAppointments' => $this->appointmentRepository->findHistoryForMedecinPatient($medecinProfile, $patient),
            'patientRecords' => $this->consultationRepository->findCompletedForPatient($patient),
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
