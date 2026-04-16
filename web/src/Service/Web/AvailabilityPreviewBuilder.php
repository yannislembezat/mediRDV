<?php

declare(strict_types=1);

namespace App\Service\Web;

use App\Entity\MedecinProfile;
use App\Service\AvailabilityService;
use App\Service\Availability\AvailableSlot;

final class AvailabilityPreviewBuilder
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
    ) {
    }

    /**
     * @return list<array{
     *     date: \DateTimeImmutable,
     *     dateKey: string,
     *     slots: list<array{label: string, value: string, startsAt: \DateTimeImmutable, endsAt: \DateTimeImmutable}>
     * }>
     */
    public function buildForMedecin(
        MedecinProfile $medecin,
        int $daysToScan = 21,
        int $maxDates = 4,
        int $maxSlotsPerDate = 5,
    ): array {
        $days = [];
        $cursor = (new \DateTimeImmutable())->setTime(0, 0);

        for ($offset = 0; $offset < $daysToScan && count($days) < $maxDates; ++$offset) {
            $date = $cursor->modify(sprintf('+%d day', $offset));
            $slots = $this->availabilityService->getFreeSlots($medecin, $date);

            if ($slots === []) {
                continue;
            }

            $days[] = [
                'date' => $date,
                'dateKey' => $date->format('Y-m-d'),
                'slots' => array_map(
                    static fn (AvailableSlot $slot): array => [
                        'label' => sprintf('%s - %s', $slot->getStartsAt()->format('H:i'), $slot->getEndsAt()->format('H:i')),
                        'value' => $slot->getStartsAt()->format(\DateTimeInterface::ATOM),
                        'startsAt' => $slot->getStartsAt(),
                        'endsAt' => $slot->getEndsAt(),
                    ],
                    array_slice($slots, 0, $maxSlotsPerDate),
                ),
            ];
        }

        return $days;
    }
}
