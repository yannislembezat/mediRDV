<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\MedicationForm;
use App\Repository\MedicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedicationRepository::class)]
#[ORM\Table(name: 'medication')]
#[ORM\Index(name: 'idx_med_name', columns: ['name'])]
#[ORM\Index(name: 'idx_med_category', columns: ['category'])]
final class Medication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $name = '';

    #[ORM\Column(length: 200, nullable: true, name: 'generic_name')]
    private ?string $genericName = null;

    #[ORM\Column(length: 100, nullable: true, name: 'default_dosage')]
    private ?string $defaultDosage = null;

    #[ORM\Column(length: 20, enumType: MedicationForm::class, options: ['default' => 'comprimé'])]
    private MedicationForm $form = MedicationForm::COMPRIME;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, PrescriptionItem> */
    #[ORM\OneToMany(mappedBy: 'medication', targetEntity: PrescriptionItem::class)]
    private Collection $prescriptionItems;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->prescriptionItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = trim($name);

        return $this;
    }

    public function getGenericName(): ?string
    {
        return $this->genericName;
    }

    public function setGenericName(?string $genericName): self
    {
        $this->genericName = $genericName !== null ? trim($genericName) : null;

        return $this;
    }

    public function getDefaultDosage(): ?string
    {
        return $this->defaultDosage;
    }

    public function setDefaultDosage(?string $defaultDosage): self
    {
        $this->defaultDosage = $defaultDosage !== null ? trim($defaultDosage) : null;

        return $this;
    }

    public function getForm(): MedicationForm
    {
        return $this->form;
    }

    public function setForm(MedicationForm $form): self
    {
        $this->form = $form;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category !== null ? trim($category) : null;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
