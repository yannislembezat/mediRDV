<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\Gender;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'uk_user_email', columns: ['email'])]
#[ORM\Index(name: 'idx_user_active', columns: ['is_active'])]
#[ORM\Index(name: 'idx_user_name', columns: ['last_name', 'first_name'])]
#[ORM\HasLifecycleCallbacks]
final class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 255)]
    private string $password = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '["ROLE_PATIENT"] | ["ROLE_ADMIN"] | ["ROLE_MEDECIN"]'])]
    private array $roles = [UserRole::PATIENT->value];

    #[ORM\Column(length: 100, name: 'first_name')]
    private string $firstName = '';

    #[ORM\Column(length: 100, name: 'last_name')]
    private string $lastName = '';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, name: 'date_of_birth')]
    private ?\DateTimeImmutable $dateOfBirth = null;

    #[ORM\Column(length: 1, enumType: Gender::class, nullable: true)]
    private ?Gender $gender = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true, name: 'avatar_url')]
    private ?string $avatarUrl = null;

    #[ORM\Column(options: ['default' => true], name: 'is_active')]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, name: 'email_verified_at')]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: MedecinProfile::class, cascade: ['persist', 'remove'])]
    private ?MedecinProfile $medecinProfile = null;

    /** @var Collection<int, Appointment> */
    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: Appointment::class)]
    private Collection $patientAppointments;

    /** @var Collection<int, Appointment> */
    #[ORM\OneToMany(mappedBy: 'validatedBy', targetEntity: Appointment::class)]
    private Collection $validatedAppointments;

    /** @var Collection<int, Notification> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Notification::class, orphanRemoval: true)]
    private Collection $notifications;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->patientAppointments = new ArrayCollection();
        $this->validatedAppointments = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = strtolower(trim($email));

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        if ($roles === []) {
            $roles[] = UserRole::PATIENT->value;
        }

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $normalizedRoles = [];

        foreach ($roles as $role) {
            if (!is_string($role) || $role === '') {
                continue;
            }

            $normalizedRoles[] = $role;
        }

        if ($normalizedRoles === []) {
            $normalizedRoles[] = UserRole::PATIENT->value;
        }

        $this->roles = array_values(array_unique($normalizedRoles));

        return $this;
    }

    public function hasRole(UserRole $role): bool
    {
        return in_array($role->value, $this->getRoles(), true);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = trim($firstName);

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = trim($lastName);

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone !== null ? trim($phone) : null;

        return $this;
    }

    public function getDateOfBirth(): ?\DateTimeImmutable
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTimeImmutable $dateOfBirth): self
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address !== null ? trim($address) : null;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl !== null ? trim($avatarUrl) : null;

        return $this;
    }

    public function getFullName(): string
    {
        return trim(sprintf('%s %s', $this->firstName, $this->lastName));
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

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeImmutable $emailVerifiedAt): self
    {
        $this->emailVerifiedAt = $emailVerifiedAt;

        return $this;
    }

    public function markEmailVerified(): self
    {
        $this->emailVerifiedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getMedecinProfile(): ?MedecinProfile
    {
        return $this->medecinProfile;
    }

    public function setMedecinProfile(?MedecinProfile $medecinProfile): self
    {
        if ($this->medecinProfile === $medecinProfile) {
            return $this;
        }

        $this->medecinProfile = $medecinProfile;

        if ($medecinProfile !== null && $medecinProfile->getUser() !== $this) {
            $medecinProfile->setUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getPatientAppointments(): Collection
    {
        return $this->patientAppointments;
    }

    public function addPatientAppointment(Appointment $appointment): self
    {
        if (!$this->patientAppointments->contains($appointment)) {
            $this->patientAppointments->add($appointment);
            $appointment->setPatient($this);
        }

        return $this;
    }

    public function removePatientAppointment(Appointment $appointment): self
    {
        if ($this->patientAppointments->removeElement($appointment) && $appointment->getPatient() === $this) {
            $appointment->setPatient(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getValidatedAppointments(): Collection
    {
        return $this->validatedAppointments;
    }

    public function addValidatedAppointment(Appointment $appointment): self
    {
        if (!$this->validatedAppointments->contains($appointment)) {
            $this->validatedAppointments->add($appointment);
            $appointment->setValidatedBy($this);
        }

        return $this;
    }

    public function removeValidatedAppointment(Appointment $appointment): self
    {
        if ($this->validatedAppointments->removeElement($appointment) && $appointment->getValidatedBy() === $this) {
            $appointment->setValidatedBy(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): self
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): self
    {
        if ($this->notifications->removeElement($notification) && $notification->getUser() === $this) {
            $notification->setUser(null);
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable();

        if (!isset($this->createdAt)) {
            $this->createdAt = $now;
        }

        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
