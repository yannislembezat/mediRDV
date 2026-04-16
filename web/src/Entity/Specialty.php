<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SpecialtyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SpecialtyRepository::class)]
#[ORM\Table(name: 'specialty')]
#[ORM\UniqueConstraint(name: 'uk_specialty_name', columns: ['name'])]
#[ORM\UniqueConstraint(name: 'uk_specialty_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_specialty_active_order', columns: ['is_active', 'display_order'])]
final class Specialty
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(length: 120)]
    private string $slug = '';

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => "Clé d'icône Bootstrap Icons"])]
    private ?string $icon = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'display_order', options: ['default' => 0])]
    private int $displayOrder = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, MedecinProfile> */
    #[ORM\OneToMany(mappedBy: 'specialty', targetEntity: MedecinProfile::class)]
    private Collection $medecinProfiles;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->medecinProfiles = new ArrayCollection();
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = trim($slug);

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon !== null ? trim($icon) : null;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description !== null ? trim($description) : null;

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

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return Collection<int, MedecinProfile>
     */
    public function getMedecinProfiles(): Collection
    {
        return $this->medecinProfiles;
    }

    public function addMedecinProfile(MedecinProfile $medecinProfile): self
    {
        if (!$this->medecinProfiles->contains($medecinProfile)) {
            $this->medecinProfiles->add($medecinProfile);
            $medecinProfile->setSpecialty($this);
        }

        return $this;
    }

    public function removeMedecinProfile(MedecinProfile $medecinProfile): self
    {
        if ($this->medecinProfiles->removeElement($medecinProfile) && $medecinProfile->getSpecialty() === $this) {
            $medecinProfile->setSpecialty(null);
        }

        return $this;
    }
}
