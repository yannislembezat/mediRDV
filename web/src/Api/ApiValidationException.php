<?php

declare(strict_types=1);

namespace App\Api;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ApiValidationException extends HttpException
{
    /**
     * @param array<string, array<int, string>> $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Les donnees fournies sont invalides.',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(Response::HTTP_UNPROCESSABLE_ENTITY, $message, $previous);
    }

    public static function fromViolations(
        ConstraintViolationListInterface $violations,
        string $message = 'Les donnees fournies sont invalides.',
    ): self {
        $errors = [];

        foreach ($violations as $violation) {
            $path = $violation->getPropertyPath() !== '' ? $violation->getPropertyPath() : 'general';
            $errors[$path][] = $violation->getMessage();
        }

        return new self($errors, $message);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
