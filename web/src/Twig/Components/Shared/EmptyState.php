<?php

declare(strict_types=1);

namespace App\Twig\Components\Shared;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'shared:empty_state')]
final class EmptyState
{
    public string $title = '';
    public string $message = '';
    public string $icon = 'bi-inbox';
    public ?string $actionLabel = null;
    public ?string $actionHref = null;
}
