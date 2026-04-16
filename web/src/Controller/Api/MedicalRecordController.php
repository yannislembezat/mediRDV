<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\ApiResourceNormalizer;
use App\Api\ApiResponseFactory;
use App\Entity\User;
use App\Repository\ConsultationRepository;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
final class MedicalRecordController extends AbstractController
{
    #[OA\Get(
        path: '/api/medical-records',
        summary: 'Lister les consultations completes du patient',
        tags: ['Medical Records'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des dossiers medicaux', content: new OA\JsonContent(ref: '#/components/schemas/MedicalRecordCollectionResponse')),
        ],
    )]
    #[Route('/api/medical-records', name: 'api_medical_records_index', methods: ['GET'])]
    public function index(
        #[CurrentUser] User $patient,
        ConsultationRepository $consultationRepository,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $consultations = array_map(
            fn ($consultation): array => $resourceNormalizer->medicalRecordSummary($consultation),
            $consultationRepository->findCompletedForPatient($patient),
        );

        return $responseFactory->success($consultations, extra: [
            'total' => count($consultations),
        ]);
    }

    #[OA\Get(
        path: '/api/medical-records/{id}',
        summary: 'Afficher une consultation complete avec ordonnance',
        tags: ['Medical Records'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dossier medical detaille', content: new OA\JsonContent(ref: '#/components/schemas/MedicalRecordDetailResponse')),
            new OA\Response(response: 404, description: 'Dossier introuvable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    #[Route('/api/medical-records/{id}', name: 'api_medical_records_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        #[CurrentUser] User $patient,
        ConsultationRepository $consultationRepository,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $consultation = $consultationRepository->findOneCompletedForPatientById($patient, $id);

        if ($consultation === null) {
            throw new NotFoundHttpException();
        }

        return $responseFactory->success($resourceNormalizer->medicalRecordDetail($consultation));
    }
}
