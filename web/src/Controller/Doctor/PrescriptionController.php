<?php

declare(strict_types=1);

namespace App\Controller\Doctor;

use App\DTO\DoctorConsultationData;
use App\DTO\DoctorPrescriptionData;
use App\Entity\MedecinProfile;
use App\Entity\User;
use App\Form\DoctorPrescriptionType;
use App\Repository\ConsultationRepository;
use App\Repository\MedicationRepository;
use App\Service\ConsultationService;
use App\Service\Exception\WorkflowException;
use App\Service\PrescriptionService;
use App\Service\Web\DoctorPortalContextBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MEDECIN')]
final class PrescriptionController extends AbstractController
{
    public function __construct(
        private readonly ConsultationRepository $consultationRepository,
        private readonly MedicationRepository $medicationRepository,
        private readonly PrescriptionService $prescriptionService,
        private readonly ConsultationService $consultationService,
        private readonly DoctorPortalContextBuilder $doctorPortalContextBuilder,
    ) {
    }

    #[Route('/medecin/consultation/{id}/ordonnance', name: 'doctor_prescription_add', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, #[CurrentUser] User $doctor): Response
    {
        $medecinProfile = $this->requireMedecinProfile($doctor);
        $consultation = $this->consultationRepository->findOneForMedecinById($medecinProfile, $id);

        if ($consultation === null) {
            throw $this->createNotFoundException('La consultation demandee est introuvable.');
        }

        if ($consultation->isCompleted()) {
            $this->addFlash('warning', 'Une consultation finalisee ne peut plus modifier son ordonnance.');

            return $this->redirectToRoute('doctor_consultation_show', ['id' => $consultation->getId()]);
        }

        $prescriptionData = DoctorPrescriptionData::fromConsultation($consultation);
        $medications = $this->medicationRepository->findActiveCatalog();
        $prescriptionForm = $this->createForm(DoctorPrescriptionType::class, $prescriptionData, [
            'medications' => $medications,
        ]);
        $prescriptionForm->handleRequest($request);

        if ($prescriptionForm->isSubmitted() && $prescriptionForm->isValid()) {
            try {
                $this->prescriptionService->createOrUpdate(
                    $consultation,
                    $prescriptionData->getNormalizedNotes(),
                    $prescriptionData->toServiceItems(),
                );

                if ($prescriptionForm->get('finalize')->isClicked()) {
                    $this->consultationService->finalize(
                        $consultation,
                        $doctor,
                        DoctorConsultationData::fromConsultation($consultation)->toPayload(),
                    );
                    $this->addFlash('success', 'La consultation a ete finalisee et le rendez-vous marque comme complete.');

                    return $this->redirectToRoute('doctor_consultation_show', ['id' => $consultation->getId()]);
                }

                $this->addFlash('success', 'L ordonnance a ete enregistree.');

                return $this->redirectToRoute('doctor_prescription_add', ['id' => $consultation->getId()]);
            } catch (WorkflowException $exception) {
                $this->addFlash('warning', $exception->getMessage());
            }
        }

        return $this->render('doctor/prescriptions/form.html.twig', array_merge([
            'doctorProfile' => $medecinProfile,
            'consultation' => $consultation,
            'prescriptionForm' => $prescriptionForm->createView(),
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
