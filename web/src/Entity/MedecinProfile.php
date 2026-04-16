<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MedecinProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MedecinProfileRepository::class)]
#[ORM\Table(name: 'medecin_profile')]
#[ORM\UniqueConstraint(name: 'uk_medecin_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_medecin_specialty', columns: ['specialty_id'])]
#[ORM\HasLifecycleCallbacks]
final class MedecinProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'medecinProfile', targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'medecinProfiles', targetEntity: Specialty::class)]
    #[ORM\JoinColumn(name: 'specialty_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?Specialty $specialty = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(name: 'consultation_duration', options: ['default' => 30])]
    private int $consultationDuration = 30;

    #[ORM\Column(length: 255, nullable: true, name: 'office_location')]
    private ?string $officeLocation = null;

    #[ORM\Column(nullable: true, name: 'years_experience')]
    private ?int $yearsExperience = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $diploma = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, Availability> */
    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: Availability::class, orphanRemoval: true)]
    #[ORM\OrderBy(['isRecurring' => 'DESC', 'specificDate' => 'ASC', 'dayOfWeek' => 'ASC', 'startTime' => 'ASC'])]
    private Collection $availabilities;

    /** @var Collection<int, Appointment> */
    #[ORM\OneToMany(mappedBy: 'medecin', targetEntity: Appointment::class)]
    private Collection $appointments;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->availabilities = new ArrayCollection();
        $this->appointments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        if ($user !== null && $user->getMedecinProfile() !== $this) {
            $user->setMedecinProfile($this);
        }

        return $this;
    }

    public function getSpecialty(): ?Specialty
    {
        return $this->specialty;
    }

    public function setSpecialty(?Specialty $specialty): self
    {
        $this->specialty = $specialty;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio !== null ? trim($bio) : null;

        return $this;
    }

    public function getConsultationDuration(): int
    {
        return $this->consultationDuration;
    }

    public function setConsultationDuration(int $consultationDuration): self
    {
        $this->consultationDuration = $consultationDuration;

        return $this;
    }

    public function getOfficeLocation(): ?string
    {
        return $this->officeLocation;
    }

    public function setOfficeLocation(?string $officeLocation): self
    {
        $this->officeLocation = $officeLocation !== null ? trim($officeLocation) : null;

        return $this;
    }

    public function getYearsExperience(): ?int
    {
        return $this->yearsExperience;
    }

    public function setYearsExperience(?int $yearsExperience): self
    {
        $this->yearsExperience = $yearsExperience;

        return $this;
    }

    public function getDiploma(): ?string
    {
        return $this->diploma;
    }

    public function setDiploma(?string $diploma): self
    {
        $this->diploma = $diploma !== null ? trim($diploma) : null;

        return $this;
    }

    public function getDisplayName(): string
    {
        if ($this->user === null) {
            return 'Médecin';
        }

        return 'Dr. '.$this->user->getFullName();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, Availability>
     */
    public function getAvailabilities(): Collection
    {
        return $this->availabilities;
    }

    public function addAvailability(Availability $availability): self
    {
        if (!$this->availabilities->contains($availability)) {
            $this->availabilities->add($availability);
            $availability->setMedecin($this);
        }

        return $this;
    }

    public function removeAvailability(Availability $availability): self
    {
        if ($this->availabilities->removeElement($availability) && $availability->getMedecin() === $this) {
            $availability->setMedecin(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): self
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setMedecin($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): self
    {
        if ($this->appointments->removeElement($appointment) && $appointment->getMedecin() === $this) {
            $appointment->setMedecin(null);
        }

        return $this;
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $this->createdAt ?? $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
