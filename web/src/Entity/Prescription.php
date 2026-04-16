<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PrescriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrescriptionRepository::class)]
#[ORM\Table(name: 'prescription')]
#[ORM\UniqueConstraint(name: 'uk_prescription_consult', columns: ['consultation_id'])]
final class Prescription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'prescription', targetEntity: Consultation::class)]
    #[ORM\JoinColumn(name: 'consultation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Consultation $consultation = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, PrescriptionItem> */
    #[ORM\OneToMany(mappedBy: 'prescription', targetEntity: PrescriptionItem::class, orphanRemoval: true, cascade: ['persist'])]
    #[ORM\OrderBy(['displayOrder' => 'ASC', 'id' => 'ASC'])]
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): self
    {
        $this->consultation = $consultation;

        if ($consultation !== null && $consultation->getPrescription() !== $this) {
            $consultation->setPrescription($this);
        }

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes !== null ? trim($notes) : null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, PrescriptionItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(PrescriptionItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setPrescription($this);
        }

        return $this;
    }

    public function removeItem(PrescriptionItem $item): self
    {
        if ($this->items->removeElement($item) && $item->getPrescription() === $this) {
            $item->setPrescription(null);
        }

        return $this;
    }
}
