<?php

declare(strict_types=1);

namespace App\Twig\Components\Shared;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'shared:status_badge')]
final class StatusBadge
{
    public mixed $status = null;
    public ?string $label = null;
    public ?string $tone = null;
    public ?string $icon = null;
}
