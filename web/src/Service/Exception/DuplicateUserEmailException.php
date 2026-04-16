<?php

declare(strict_types=1);

namespace App\Service\Exception;

final class DuplicateUserEmailException extends WorkflowException
{
    public static function forEmail(string $email): self
    {
        return new self(sprintf('Un compte existe deja avec l\'adresse email "%s".', $email));
    }
}
