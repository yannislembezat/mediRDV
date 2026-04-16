<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AvailabilityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
#[ORM\Table(name: 'availability')]
#[ORM\Index(name: 'idx_avail_medecin', columns: ['medecin_id'])]
#[ORM\Index(name: 'idx_avail_day', columns: ['day_of_week'])]
#[ORM\Index(name: 'idx_avail_date', columns: ['specific_date'])]
final class Availability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'availabilities', targetEntity: MedecinProfile::class)]
    #[ORM\JoinColumn(name: 'medecin_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?MedecinProfile $medecin = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true, name: 'day_of_week', options: ['unsigned' => true])]
    private ?int $dayOfWeek = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true, name: 'specific_date')]
    private ?\DateTimeImmutable $specificDate = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, name: 'start_time')]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, name: 'end_time')]
    private \DateTimeImmutable $endTime;

    #[ORM\Column(name: 'is_recurring', options: ['default' => true])]
    private bool $isRecurring = true;

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->startTime = new \DateTimeImmutable('09:00:00');
        $this->endTime = new \DateTimeImmutable('09:30:00');
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMedecin(): ?MedecinProfile
    {
        return $this->medecin;
    }

    public function setMedecin(?MedecinProfile $medecin): self
    {
        $this->medecin = $medecin;

        return $this;
    }

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(?int $dayOfWeek): self
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getSpecificDate(): ?\DateTimeImmutable
    {
        return $this->specificDate;
    }

    public function setSpecificDate(?\DateTimeImmutable $specificDate): self
    {
        $this->specificDate = $specificDate;

        return $this;
    }

    public function getStartTime(): \DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): \DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

    public function setIsRecurring(bool $isRecurring): self
    {
        $this->isRecurring = $isRecurring;

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
