<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\ApiResourceNormalizer;
use App\Api\ApiResponseFactory;
use App\Api\ApiValidationException;
use App\Api\JsonRequestDecoder;
use App\DTO\AppointmentRequestData;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use App\Repository\AppointmentRepository;
use App\Repository\MedecinProfileRepository;
use App\Service\AppointmentService;
use Knp\Component\Pager\PaginatorInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[IsGranted('ROLE_PATIENT')]
final class AppointmentController extends AbstractController
{
    #[OA\Post(
        path: '/api/appointments',
        summary: 'Demander un rendez-vous',
        tags: ['Appointments'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['medecinId', 'dateTime'],
                properties: [
                    new OA\Property(property: 'medecinId', type: 'integer', example: 1),
                    new OA\Property(property: 'dateTime', type: 'string', format: 'date-time', example: '2026-04-20T10:00:00+00:00'),
                    new OA\Property(property: 'reason', type: 'string', nullable: true, example: 'Douleurs thoraciques intermittentes'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Rendez-vous cree', content: new OA\JsonContent(ref: '#/components/schemas/AppointmentDetailResponse')),
            new OA\Response(response: 404, description: 'Medecin introuvable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Donnees invalides ou creneau indisponible', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    #[Route('/api/appointments', name: 'api_appointments_create', methods: ['POST'])]
    public function create(
        Request $request,
        #[CurrentUser] User $patient,
        JsonRequestDecoder $jsonRequestDecoder,
        ValidatorInterface $validator,
        MedecinProfileRepository $medecinProfileRepository,
        AppointmentService $appointmentService,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $payload = $jsonRequestDecoder->decode($request);
        $appointmentData = $this->hydrateAppointmentRequestData($payload);
        $violations = $validator->validate($appointmentData);

        if (count($violations) > 0) {
            throw ApiValidationException::fromViolations($violations, 'Les donnees du rendez-vous sont invalides.');
        }

        $medecin = $medecinProfileRepository->findOneWithAvailability($appointmentData->medecinId ?? 0);

        if ($medecin === null) {
            throw new NotFoundHttpException();
        }

        $appointment = $appointmentService->request(
            $patient,
            $medecin,
            $appointmentData->getDateTimeAsDateTimeImmutable(),
            $this->normalizeNullableString($appointmentData->reason),
        );

        return $responseFactory->success(
            $resourceNormalizer->appointmentDetail($appointment),
            Response::HTTP_CREATED,
            [],
            'Rendez-vous demande avec succes.',
        );
    }

    #[OA\Get(
        path: '/api/appointments',
        summary: 'Lister ses rendez-vous avec filtres et pagination',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'pending,confirmed')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginee', content: new OA\JsonContent(ref: '#/components/schemas/AppointmentCollectionResponse')),
            new OA\Response(response: 422, description: 'Filtres invalides', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    #[Route('/api/appointments', name: 'api_appointments_index', methods: ['GET'])]
    public function index(
        Request $request,
        #[CurrentUser] User $patient,
        AppointmentRepository $appointmentRepository,
        PaginatorInterface $paginator,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $statuses = $this->parseStatuses($request->query->get('status'));
        $page = $this->parsePositiveInt($request->query->get('page', 1), 'page', 1);
        $limit = $this->parsePositiveInt($request->query->get('limit', 10), 'limit', 10, 50);
        $pagination = $paginator->paginate(
            $appointmentRepository->createPatientListQueryBuilder($patient, $statuses),
            $page,
            $limit,
        );

        $items = array_map(
            fn ($appointment): array => $resourceNormalizer->appointmentSummary($appointment),
            iterator_to_array($pagination),
        );
        $total = $pagination->getTotalItemCount();

        return $responseFactory->success($items, extra: [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => $limit > 0 ? (int) ceil($total / $limit) : 1,
            'filters' => [
                'status' => array_map(static fn (AppointmentStatus $status): string => $status->value, $statuses),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/appointments/{id}',
        summary: 'Afficher le detail d un rendez-vous patient',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detail du rendez-vous', content: new OA\JsonContent(ref: '#/components/schemas/AppointmentDetailResponse')),
            new OA\Response(response: 404, description: 'Rendez-vous introuvable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    #[Route('/api/appointments/{id}', name: 'api_appointments_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        #[CurrentUser] User $patient,
        AppointmentRepository $appointmentRepository,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $appointment = $appointmentRepository->findOneForPatientById($patient, $id);

        if ($appointment === null) {
            throw new NotFoundHttpException();
        }

        return $responseFactory->success($resourceNormalizer->appointmentDetail($appointment));
    }

    #[OA\Delete(
        path: '/api/appointments/{id}',
        summary: 'Annuler un rendez-vous en attente',
        tags: ['Appointments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Rendez-vous annule', content: new OA\JsonContent(ref: '#/components/schemas/AppointmentDetailResponse')),
            new OA\Response(response: 404, description: 'Rendez-vous introuvable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Annulation impossible', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    #[Route('/api/appointments/{id}', name: 'api_appointments_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        #[CurrentUser] User $patient,
        AppointmentRepository $appointmentRepository,
        AppointmentService $appointmentService,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $appointment = $appointmentRepository->findOneForPatientById($patient, $id);

        if ($appointment === null) {
            throw new NotFoundHttpException();
        }

        $cancelledAppointment = $appointmentService->cancelByPatient($appointment, $patient);

        return $responseFactory->success(
            $resourceNormalizer->appointmentDetail($cancelledAppointment),
            Response::HTTP_OK,
            [],
            'Rendez-vous annule avec succes.',
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateAppointmentRequestData(array $payload): AppointmentRequestData
    {
        $appointmentData = new AppointmentRequestData();
        $appointmentData->medecinId = $this->extractPositiveInt($payload['medecinId'] ?? null);
        $appointmentData->dateTime = $this->extractString($payload, 'dateTime') ?? '';
        $appointmentData->reason = $this->extractNullableString($payload, 'reason');

        return $appointmentData;
    }

    /**
     * @return list<AppointmentStatus>
     */
    private function parseStatuses(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_scalar($value)) {
            throw new ApiValidationException([
                'status' => ['Le filtre de statut est invalide.'],
            ], 'Les filtres fournis sont invalides.');
        }

        $statuses = [];

        foreach (explode(',', trim((string) $value)) as $statusValue) {
            $normalizedStatus = trim($statusValue);

            if ($normalizedStatus === '') {
                continue;
            }

            $status = AppointmentStatus::tryFrom($normalizedStatus);

            if ($status === null) {
                throw new ApiValidationException([
                    'status' => [sprintf('Le statut "%s" est invalide.', $normalizedStatus)],
                ], 'Les filtres fournis sont invalides.');
            }

            $statuses[$status->value] = $status;
        }

        return array_values($statuses);
    }

    private function parsePositiveInt(mixed $value, string $field, int $default, ?int $max = null): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            throw new ApiValidationException([
                $field => ['Ce parametre doit etre un entier positif.'],
            ], 'Les filtres fournis sont invalides.');
        }

        $normalizedValue = (int) $value;

        if ($max !== null && $normalizedValue > $max) {
            throw new ApiValidationException([
                $field => [sprintf('Ce parametre ne peut pas depasser %d.', $max)],
            ], 'Les filtres fournis sont invalides.');
        }

        return $normalizedValue;
    }

    private function extractPositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        $normalizedValue = (int) $value;

        return $normalizedValue > 0 ? $normalizedValue : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractString(array $payload, string $key): ?string
    {
        if (!array_key_exists($key, $payload)) {
            return null;
        }

        return is_scalar($payload[$key]) ? trim((string) $payload[$key]) : null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractNullableString(array $payload, string $key): ?string
    {
        $value = $this->extractString($payload, $key);

        return $value !== null && $value !== '' ? $value : null;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalizedValue = trim($value);

        return $normalizedValue !== '' ? $normalizedValue : null;
    }
}
