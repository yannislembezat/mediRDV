<?php

declare(strict_types=1);

namespace App\Twig\Components\Shared;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'shared:doctor_card')]
final class DoctorCard
{
    public mixed $doctor = null;
    public ?string $actionLabel = null;
    public ?string $actionHref = null;
    public ?string $summary = null;

    /** @var list<string> */
    public array $tags = [];
}
