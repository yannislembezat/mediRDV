<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\ApiResponseFactory;
use App\Api\ApiResourceNormalizer;
use App\Api\ApiValidationException;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PATIENT')]
final class NotificationController extends AbstractController
{
    #[OA\Get(
        path: '/api/notifications',
        summary: 'Lister les notifications du patient',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(name: 'isRead', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50, default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des notifications', content: new OA\JsonContent(ref: '#/components/schemas/NotificationCollectionResponse')),
            new OA\Response(response: 422, description: 'Filtres invalides', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    #[Route('/api/notifications', name: 'api_notifications_index', methods: ['GET'])]
    public function index(
        Request $request,
        #[CurrentUser] User $user,
        NotificationRepository $notificationRepository,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $normalizedIsRead = $this->parseNullableBool($request->query->get('isRead'), 'isRead');
        $normalizedLimit = $this->parsePositiveInt($request->query->get('limit'), 'limit', 20, 50);
        $notifications = array_map(
            fn ($notification): array => $resourceNormalizer->notification($notification),
            $notificationRepository->findLatestForUser($user, $normalizedIsRead, $normalizedLimit),
        );

        return $responseFactory->success($notifications, extra: [
            'total' => count($notifications),
            'limit' => $normalizedLimit,
            'unreadCount' => $notificationRepository->countUnreadForUser($user),
        ]);
    }

    #[OA\Patch(
        path: '/api/notifications/{id}/read',
        summary: 'Marquer une notification comme lue',
        tags: ['Notifications'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification mise a jour', content: new OA\JsonContent(ref: '#/components/schemas/NotificationResponse')),
            new OA\Response(response: 404, description: 'Notification introuvable', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    #[Route('/api/notifications/{id}/read', name: 'api_notifications_mark_read', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function markAsRead(
        int $id,
        #[CurrentUser] User $user,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $notification = $notificationRepository->findOneForUserById($user, $id);

        if ($notification === null) {
            throw new NotFoundHttpException();
        }

        if (!$notification->isRead()) {
            $notification->markAsRead();
            $entityManager->flush();
        }

        return $responseFactory->success(
            $resourceNormalizer->notification($notification),
            Response::HTTP_OK,
            [],
            'Notification marquee comme lue.',
        );
    }

    private function parseNullableBool(?string $value, string $field): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalizedValue = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($normalizedValue === null) {
            throw new ApiValidationException([
                $field => ['Ce parametre doit etre un booleen.'],
            ], 'Les filtres fournis sont invalides.');
        }

        return $normalizedValue;
    }

    private function parsePositiveInt(?string $value, string $field, int $default, ?int $max = null): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
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
}
