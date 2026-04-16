<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Consultation;
use App\Entity\Prescription;
use App\Entity\PrescriptionItem;
use App\Enum\AppointmentStatus;
use App\Service\Exception\WorkflowException;
use App\Service\Prescription\PrescriptionItemData;
use Doctrine\ORM\EntityManagerInterface;

final class PrescriptionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<PrescriptionItemData> $items
     */
    public function create(Consultation $consultation, ?string $notes = null, array $items = []): Prescription
    {
        $this->assertConsultationCanManagePrescription($consultation);

        if ($consultation->getPrescription() !== null) {
            throw new WorkflowException('Une ordonnance existe déjà pour cette consultation.');
        }

        $prescription = (new Prescription())
            ->setConsultation($consultation)
            ->setNotes($notes);

        $this->synchronizeItems($prescription, $items);
        $this->entityManager->persist($prescription);
        $this->entityManager->flush();

        return $prescription;
    }

    /**
     * @param list<PrescriptionItemData> $items
     */
    public function update(Prescription $prescription, ?string $notes = null, array $items = []): Prescription
    {
        $consultation = $prescription->getConsultation();

        if ($consultation === null) {
            throw new WorkflowException('L\'ordonnance doit être rattachée à une consultation.');
        }

        $this->assertConsultationCanManagePrescription($consultation);

        $prescription->setNotes($notes);
        $this->synchronizeItems($prescription, $items);
        $this->entityManager->flush();

        return $prescription;
    }

    /**
     * @param list<PrescriptionItemData> $items
     */
    public function createOrUpdate(Consultation $consultation, ?string $notes = null, array $items = []): Prescription
    {
        $existingPrescription = $consultation->getPrescription();

        if ($existingPrescription !== null) {
            return $this->update($existingPrescription, $notes, $items);
        }

        return $this->create($consultation, $notes, $items);
    }

    /**
     * @param list<PrescriptionItemData> $items
     */
    private function synchronizeItems(Prescription $prescription, array $items): void
    {
        foreach ($items as $item) {
            if (!$item instanceof PrescriptionItemData) {
                throw new \InvalidArgumentException('Les lignes d\'ordonnance doivent être fournies sous forme de PrescriptionItemData.');
            }
        }

        foreach ($prescription->getItems()->toArray() as $existingItem) {
            $prescription->removeItem($existingItem);
        }

        foreach ($items as $index => $itemData) {
            $prescription->addItem(
                (new PrescriptionItem())
                    ->setMedication($itemData->medication)
                    ->setCustomName($itemData->customName)
                    ->setDosage($itemData->dosage)
                    ->setDuration($itemData->duration)
                    ->setFrequency($itemData->frequency)
                    ->setInstructions($itemData->instructions)
                    ->setDisplayOrder($itemData->displayOrder ?? $index),
            );
        }
    }

    private function assertConsultationCanManagePrescription(Consultation $consultation): void
    {
        if ($consultation->isCompleted()) {
            throw new WorkflowException('Une consultation finalisée ne peut plus modifier son ordonnance.');
        }

        if ($consultation->getAppointment()?->getStatus() !== AppointmentStatus::CONFIRMED) {
            throw new WorkflowException('Une ordonnance ne peut être gérée que pour une consultation liée à un rendez-vous confirmé.');
        }
    }
}
