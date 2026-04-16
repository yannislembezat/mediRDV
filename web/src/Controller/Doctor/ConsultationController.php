<?php

declare(strict_types=1);

namespace App\Controller\Doctor;

use App\Entity\MedecinProfile;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Repository\ConsultationRepository;
use App\Service\ConsultationService;
use App\Service\Exception\WorkflowException;
use App\Service\Web\DoctorPortalContextBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MEDECIN')]
final class ConsultationController extends AbstractController
{
    public function __construct(
        private readonly AppointmentRepository $appointmentRepository,
        private readonly ConsultationRepository $consultationRepository,
        private readonly ConsultationService $consultationService,
        private readonly DoctorPortalContextBuilder $doctorPortalContextBuilder,
    ) {
    }

    #[Route('/medecin/rdv/{id}/consultation', name: 'doctor_consultation_start', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function start(int $id, #[CurrentUser] User $doctor): Response
    {
        $medecinProfile = $this->requireMedecinProfile($doctor);
        $appointment = $this->appointmentRepository->findOneForMedecinById($medecinProfile, $id);

        if ($appointment === null) {
            throw $this->createNotFoundException('Le rendez-vous demande est introuvable.');
        }

        if ($appointment->getConsultation()?->isCompleted()) {
            return $this->redirectToRoute('doctor_consultation_show', ['id' => $appointment->getConsultation()?->getId()]);
        }

        try {
            $consultation = $this->consultationService->open($appointment, $doctor);
        } catch (WorkflowException $exception) {
            $this->addFlash('warning', $exception->getMessage());

            return $this->redirectToRoute('doctor_appointment_show', ['id' => $appointment->getId()]);
        }

        return $this->redirectToRoute('doctor_prescription_add', ['id' => $consultation->getId()]);
    }

    #[Route('/medecin/consultation/{id}', name: 'doctor_consultation_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, #[CurrentUser] User $doctor): Response
    {
        $medecinProfile = $this->requireMedecinProfile($doctor);
        $consultation = $this->consultationRepository->findOneForMedecinById($medecinProfile, $id);

        if ($consultation === null) {
            throw $this->createNotFoundException('La consultation demandee est introuvable.');
        }

        if (!$consultation->isCompleted()) {
            $appointmentId = $consultation->getAppointment()?->getId();

            if ($appointmentId === null) {
                throw $this->createNotFoundException('La consultation demandee est introuvable.');
            }

            $this->addFlash('info', 'La consultation est encore en cours. Vous pouvez reprendre le formulaire pour la finaliser.');

            return $this->redirectToRoute('doctor_consultation_start', ['id' => $appointmentId]);
        }

        return $this->render('doctor/consultations/show.html.twig', array_merge([
            'doctorProfile' => $medecinProfile,
            'consultation' => $consultation,
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
