<?php

declare(strict_types=1);

namespace App\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class JsonRequestDecoder
{
    /**
     * @return array<string, mixed>
     */
    public function decode(Request $request): array
    {
        try {
            $payload = $request->toArray();
        } catch (\JsonException) {
            throw new BadRequestHttpException('Le corps de la requete doit contenir un JSON valide.');
        }

        if (!is_array($payload) || array_is_list($payload)) {
            throw new BadRequestHttpException('Le corps de la requete doit contenir un objet JSON.');
        }

        return $payload;
    }
}
