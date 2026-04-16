<?php

declare(strict_types=1);

namespace App\Service\Availability;

final readonly class AvailableSlot
{
    public function __construct(
        private \DateTimeImmutable $startsAt,
        private \DateTimeImmutable $endsAt,
    ) {
        if ($this->endsAt <= $this->startsAt) {
            throw new \InvalidArgumentException('La fin du créneau doit être postérieure au début.');
        }
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function matchesStart(\DateTimeInterface $startsAt): bool
    {
        return $this->startsAt->format(\DateTimeInterface::ATOM) === \DateTimeImmutable::createFromInterface($startsAt)->format(\DateTimeInterface::ATOM);
    }
}
