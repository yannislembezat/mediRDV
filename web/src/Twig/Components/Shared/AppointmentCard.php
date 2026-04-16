<?php

declare(strict_types=1);

namespace App\Twig\Components\Shared;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(name: 'shared:appointment_card')]
final class AppointmentCard
{
    public mixed $appointment = null;
    public ?string $heading = null;
    public ?string $subheading = null;
    public ?string $eyebrow = null;
    public bool $showReason = false;
    public bool $showAdminNote = true;
    public ?string $actionLabel = null;
    public ?string $actionHref = null;

    /** @var list<string> */
    public array $meta = [];
}
