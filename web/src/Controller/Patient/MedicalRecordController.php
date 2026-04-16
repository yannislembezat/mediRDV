<?php

declare(strict_types=1);

namespace App\Controller\Patient;

use App\Entity\User;
use App\Repository\ConsultationRepository;
use App\Service\Web\PatientPortalContextBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
final class MedicalRecordController extends AbstractController
{
    public function __construct(
        private readonly ConsultationRepository $consultationRepository,
        private readonly PatientPortalContextBuilder $patientPortalContextBuilder,
    ) {
    }

    #[Route('/patient/dossier-medical', name: 'patient_records', methods: ['GET'])]
    public function index(#[CurrentUser] User $patient): Response
    {
        return $this->render('patient/medical_records/list.html.twig', array_merge([
            'records' => $this->consultationRepository->findCompletedForPatient($patient),
        ], $this->patientPortalContextBuilder->build($patient)));
    }

    #[Route('/patient/dossier-medical/{id}', name: 'patient_record_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, #[CurrentUser] User $patient): Response
    {
        $record = $this->consultationRepository->findOneCompletedForPatientById($patient, $id);

        if ($record === null) {
            throw $this->createNotFoundException('Le dossier medical demande est introuvable.');
        }

        return $this->render('patient/medical_records/detail.html.twig', array_merge([
            'record' => $record,
        ], $this->patientPortalContextBuilder->build($patient)));
    }
}
