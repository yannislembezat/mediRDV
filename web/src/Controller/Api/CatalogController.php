<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\ApiResponseFactory;
use App\Api\ApiResourceNormalizer;
use App\Api\ApiValidationException;
use App\Repository\MedecinProfileRepository;
use App\Repository\SpecialtyRepository;
use App\Service\AvailabilityService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    #[OA\Get(
        path: '/api/specialties',
        summary: 'Lister les specialites',
        tags: ['Catalog'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des specialites', content: new OA\JsonContent(ref: '#/components/schemas/SpecialtyCollectionResponse')),
            new OA\Response(response: 401, description: 'Authentification requise', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    #[Route('/api/specialties', name: 'api_specialties_index', methods: ['GET'])]
    public function specialties(
        SpecialtyRepository $specialtyRepository,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $specialties = array_map(
            fn ($specialty): array => $resourceNormalizer->specialty($specialty),
            $specialtyRepository->findActiveOrdered(),
        );

        return $responseFactory->success($specialties, extra: [
            'total' => count($specialties),
        ]);
    }

    #[OA\Get(
        path: '/api/medecins',
        summary: 'Lister les medecins avec filtres',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'specialtyId', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des medecins', content: new OA\JsonContent(ref: '#/components/schemas/DoctorCollectionResponse')),
            new OA\Response(response: 422, description: 'Filtres invalides', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    #[Route('/api/medecins', name: 'api_medecins_index', methods: ['GET'])]
    public function medecins(
        Request $request,
        MedecinProfileRepository $medecinProfileRepository,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $search = $this->normalizeNullableString($request->query->get('search'));
        $specialtyId = $this->parseNullablePositiveInt($request->query->get('specialtyId'), 'specialtyId');
        $medecins = array_map(
            fn ($medecin): array => $resourceNormalizer->medecinSummary($medecin),
            $medecinProfileRepository->findActiveByFilters($search, $specialtyId),
        );

        return $responseFactory->success($medecins, extra: [
            'total' => count($medecins),
            'filters' => [
                'search' => $search,
                'specialtyId' => $specialtyId,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/medecins/{id}',
        summary: 'Afficher le profil detaille d un medecin',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Profil detaille', content: new OA\JsonContent(ref: '#/components/schemas/DoctorDetailResponse')),
            new OA\Response(response: 404, description: 'Medecin introuvable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    #[Route('/api/medecins/{id}', name: 'api_medecins_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        MedecinProfileRepository $medecinProfileRepository,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $medecin = $medecinProfileRepository->findOneWithAvailability($id);

        if ($medecin === null) {
            throw new NotFoundHttpException();
        }

        return $responseFactory->success($resourceNormalizer->medecinDetail($medecin));
    }

    #[OA\Get(
        path: '/api/medecins/{id}/slots',
        summary: 'Lister les creneaux libres d un medecin a une date donnee',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Creneaux libres', content: new OA\JsonContent(ref: '#/components/schemas/SlotCollectionResponse')),
            new OA\Response(response: 404, description: 'Medecin introuvable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Date invalide', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    #[Route('/api/medecins/{id}/slots', name: 'api_medecins_slots', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function slots(
        int $id,
        Request $request,
        MedecinProfileRepository $medecinProfileRepository,
        AvailabilityService $availabilityService,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $medecin = $medecinProfileRepository->findOneWithAvailability($id);

        if ($medecin === null) {
            throw new NotFoundHttpException();
        }

        $date = $this->parseRequiredDate($request->query->get('date'), 'date');
        $slots = array_map(
            fn ($slot): array => $resourceNormalizer->slot($slot),
            $availabilityService->getFreeSlots($medecin, $date),
        );

        return $responseFactory->success($slots, extra: [
            'date' => $date->format('Y-m-d'),
            'medecinId' => $medecin->getId(),
            'total' => count($slots),
        ]);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function parseNullablePositiveInt(mixed $value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new ApiValidationException([
                $field => ['Ce filtre doit etre un entier positif.'],
            ], 'Les filtres fournis sont invalides.');
        }

        return (int) $value;
    }

    private function parseRequiredDate(mixed $value, string $field): \DateTimeImmutable
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            throw new ApiValidationException([
                $field => ['Ce champ est obligatoire.'],
            ], 'Les parametres de requete sont invalides.');
        }

        $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', trim((string) $value));

        if (!$parsedDate instanceof \DateTimeImmutable) {
            throw new ApiValidationException([
                $field => ['La date doit etre au format AAAA-MM-JJ.'],
            ], 'Les parametres de requete sont invalides.');
        }

        return $parsedDate;
    }
}
