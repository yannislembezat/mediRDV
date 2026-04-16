<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\ApiResourceNormalizer;
use App\Api\ApiResponseFactory;
use App\Api\ApiValidationException;
use App\Api\JsonRequestDecoder;
use App\DTO\PatientRegistrationData;
use App\Service\Exception\DuplicateUserEmailException;
use App\Service\UserService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthController extends AbstractController
{
    #[OA\Post(
        path: '/api/register',
        summary: 'Inscrire un patient',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'firstName', 'lastName'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ahmed@example.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'SecureP@ss123'),
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
            new OA\Response(response: 201, description: 'Compte cree', content: new OA\JsonContent(ref: '#/components/schemas/UserProfileResponse')),
            new OA\Response(response: 409, description: 'Email deja utilise', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
            new OA\Response(response: 422, description: 'Donnees invalides', content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')),
        ],
    )]
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator,
        UserService $userService,
        JsonRequestDecoder $jsonRequestDecoder,
        ApiResponseFactory $responseFactory,
        ApiResourceNormalizer $resourceNormalizer,
    ): JsonResponse {
        $payload = $jsonRequestDecoder->decode($request);
        $registrationData = $this->hydrateRegistrationData($payload);
        $violations = $validator->validate($registrationData);

        if (count($violations) > 0) {
            throw ApiValidationException::fromViolations($violations, 'Les donnees d\'inscription sont invalides.');
        }

        try {
            $user = $userService->registerPatient($registrationData);
        } catch (DuplicateUserEmailException $exception) {
            return $responseFactory->error($exception->getMessage(), Response::HTTP_CONFLICT, [
                'email' => [$exception->getMessage()],
            ]);
        }

        return $responseFactory->success(
            $resourceNormalizer->user($user),
            Response::HTTP_CREATED,
            [],
            'Le compte patient a ete cree avec succes.',
        );
    }

    #[OA\Post(
        path: '/api/login',
        summary: 'Se connecter et obtenir un JWT',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ahmed.benali@example.fr'),
                    new OA\Property(property: 'password', type: 'string', example: 'patient123'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'JWT genere', content: new OA\JsonContent(ref: '#/components/schemas/AuthTokensResponse')),
            new OA\Response(response: 401, description: 'Identifiants invalides', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    #[Route('/api/login', name: 'api_login_check', methods: ['POST'])]
    public function loginCheck(): never
    {
        throw new \LogicException('Cette action est interceptee par le firewall JWT.');
    }

    #[OA\Post(
        path: '/api/token/refresh',
        summary: 'Renouveler le JWT',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refreshToken'],
                properties: [
                    new OA\Property(property: 'refreshToken', type: 'string', example: 'refresh-token-value'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Jeton renouvele', content: new OA\JsonContent(ref: '#/components/schemas/AuthTokensResponse')),
            new OA\Response(response: 401, description: 'Refresh token invalide', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    #[Route('/api/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    public function refreshToken(): never
    {
        throw new \LogicException('Cette action est interceptee par le firewall de refresh token.');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function hydrateRegistrationData(array $payload): PatientRegistrationData
    {
        $registrationData = new PatientRegistrationData();
        $registrationData->email = $this->extractString($payload, 'email') ?? '';
        $registrationData->plainPassword = $this->extractString($payload, 'password') ?? '';
        $registrationData->firstName = $this->extractString($payload, 'firstName') ?? '';
        $registrationData->lastName = $this->extractString($payload, 'lastName') ?? '';
        $registrationData->phone = $this->extractNullableString($payload, 'phone');
        $registrationData->dateOfBirth = $this->extractNullableString($payload, 'dateOfBirth');
        $registrationData->gender = $this->extractNullableString($payload, 'gender');
        $registrationData->address = $this->extractNullableString($payload, 'address');

        return $registrationData;
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
