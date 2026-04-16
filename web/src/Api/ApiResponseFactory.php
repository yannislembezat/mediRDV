<?php

declare(strict_types=1);

namespace App\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponseFactory
{
    /**
     * @param array<string, mixed> $extra
     */
    public function success(
        mixed $data = null,
        int $status = Response::HTTP_OK,
        array $extra = [],
        ?string $message = null,
    ): JsonResponse {
        $payload = [];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($data !== null) {
            $payload['data'] = $data;
        }

        foreach ($extra as $key => $value) {
            $payload[$key] = $value;
        }

        return new JsonResponse($payload, $status);
    }

    /**
     * @param array<string, array<int, string>> $errors
     */
    public function error(
        string $message,
        int $status,
        array $errors = [],
    ): JsonResponse {
        $payload = [
            'code' => $status,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return new JsonResponse($payload, $status);
    }

    /**
     * @param array<string, array<int, string>> $errors
     */
    public function validationError(
        array $errors,
        string $message = 'Les donnees fournies sont invalides.',
    ): JsonResponse {
        return $this->error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }
}
