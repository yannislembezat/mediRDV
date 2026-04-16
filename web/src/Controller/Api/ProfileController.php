<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\ApiResourceNormalizer;
use App\Api\ApiResponseFactory;
use App\Api\ApiValidationException;
use App\Api\JsonRequestDecoder;
use App\DTO\PatientProfileUpdateData;
use App\Entity\User;
use App\Service\Exception\DuplicateUserEmailException;
use App\Service\UserService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ProfileController extends AbstractController
{
    #[OA\Get(
        path: '/api/me',
        summary: 'Recuperer le profil courant',
        tags: ['Profile'],
        responses: [
            new OA\Response(response: 200, description: 'Profil utilisateur', content: new OA\JsonContent(ref: '#/components/schemas/UserProfileResponse')),
            new OA\Response(response: 401, description: 'Authentification requise', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    #[Route('/api/me', name: 'api_me_show', methods: ['GET'])]
    public function show(
        #[CurrentUser] User $user,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        return $responseFactory->success($resourceNormalizer->user($user));
    }

    #[OA\Put(
        path: '/api/me',
        summary: 'Mettre a jour son profil',
        tags: ['Profile'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'firstName', 'lastName'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ahmed.updated@example.com'),
                    new OA\Property(property: 'firstName', type: 'string', example: 'Ahmed'),
                    new OA\Property(property: 'lastName', type: 'string', example: 'Benali'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+33612345678'),
                    new OA\Property(property: 'dateOfBirth', type: 'string', format: 'date', nullable: true, example: '1990-05-15'),
                    new OA\Property(property: 'gender', type: 'string', nullable: true, example: 'M'),
                    new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Paris - 15ème'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profil mis a jour', content: new OA\JsonContent(ref: '#/components/schemas/UserProfileResponse')),
            new OA\Response(response: 409, description: 'Email deja utilise', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 422, description: 'Donnees invalides', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    #[Route('/api/me', name: 'api_me_update', methods: ['PUT'])]
    public function update(
        Request $request,
        #[CurrentUser] User $user,
        JsonRequestDecoder $jsonRequestDecoder,
        ValidatorInterface $validator,
        UserService $userService,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $payload = $jsonRequestDecoder->decode($request);
        $profileData = $this->hydrateProfileUpdateData($payload);
        $violations = $validator->validate($profileData);

        if (count($violations) > 0) {
            throw ApiValidationException::fromViolations($violations, 'Les donnees du profil sont invalides.');
        }

        try {
            $updatedUser = $userService->updateProfile($user, $profileData);
        } catch (DuplicateUserEmailException $exception) {
            return $responseFactory->error($exception->getMessage(), Response::HTTP_CONFLICT, [
                'email' => [$exception->getMessage()],
            ]);
        }

        return $responseFactory->success(
            $resourceNormalizer->user($updatedUser),
            Response::HTTP_OK,
            [],
            'Profil mis a jour avec succes.',
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateProfileUpdateData(array $payload): PatientProfileUpdateData
    {
        $profileData = new PatientProfileUpdateData();
        $profileData->email = $this->extractString($payload, 'email') ?? '';
        $profileData->firstName = $this->extractString($payload, 'firstName') ?? '';
        $profileData->lastName = $this->extractString($payload, 'lastName') ?? '';
        $profileData->phone = $this->extractNullableString($payload, 'phone');
        $profileData->dateOfBirth = $this->extractNullableString($payload, 'dateOfBirth');
        $profileData->gender = $this->extractNullableString($payload, 'gender');
        $profileData->address = $this->extractNullableString($payload, 'address');

        return $profileData;
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
}
