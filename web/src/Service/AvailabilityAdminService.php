<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\AdminAvailabilityData;
use App\Entity\Availability;
use App\Service\Exception\WorkflowException;
use Doctrine\ORM\EntityManagerInterface;

final class AvailabilityAdminService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function create(AdminAvailabilityData $availabilityData): Availability
    {
        if ($availabilityData->medecin === null) {
            throw new WorkflowException('Le medecin est obligatoire pour creer une disponibilite.');
        }

        $availability = (new Availability())
            ->setMedecin($availabilityData->medecin)
            ->setStartTime($availabilityData->getStartTimeAsDateTimeImmutable())
            ->setEndTime($availabilityData->getEndTimeAsDateTimeImmutable())
            ->setIsRecurring($availabilityData->isRecurring())
            ->setDayOfWeek($availabilityData->isRecurring() ? $availabilityData->dayOfWeek : null)
            ->setSpecificDate($availabilityData->isRecurring() ? null : $availabilityData->getSpecificDateAsDateTimeImmutable())
            ->setIsActive(true);

        $this->entityManager->persist($availability);
        $this->entityManager->flush();

        return $availability;
    }

    public function delete(Availability $availability): void
    {
        $this->entityManager->remove($availability);
        $this->entityManager->flush();
    }
}
