<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\MedecinProfile;
use App\Entity\User;
use App\Enum\AppointmentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
final class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * @return list<Appointment>
     */
    public function findForPatient(User $patient, ?AppointmentStatus $status = null): array
    {
        $statuses = $status !== null ? [$status] : [];

        return $this->createPatientListQueryBuilder($patient, $statuses)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Appointment>
     */
    public function findForMedecin(
        MedecinProfile $medecin,
        ?AppointmentStatus $status = null,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
    ): array {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('patient')
            ->join('a.patient', 'patient')
            ->andWhere('a.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('a.dateTime', 'ASC');

        if ($status !== null) {
            $queryBuilder
                ->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        if ($from !== null) {
            $queryBuilder
                ->andWhere('a.dateTime >= :from')
                ->setParameter('from', \DateTimeImmutable::createFromInterface($from), Types::DATETIME_IMMUTABLE);
        }

        if ($to !== null) {
            $queryBuilder
                ->andWhere('a.dateTime <= :to')
                ->setParameter('to', \DateTimeImmutable::createFromInterface($to), Types::DATETIME_IMMUTABLE);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function hasConflict(
        MedecinProfile $medecin,
        \DateTimeInterface $startsAt,
        \DateTimeInterface $endsAt,
        ?int $excludeAppointmentId = null,
    ): bool {
        $queryBuilder = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.medecin = :medecin')
            ->andWhere('a.status IN (:statuses)')
            ->andWhere('a.dateTime < :endsAt')
            ->andWhere('a.endTime > :startsAt')
            ->setParameter('medecin', $medecin)
            ->setParameter('statuses', [
                AppointmentStatus::PENDING,
                AppointmentStatus::CONFIRMED,
                AppointmentStatus::COMPLETED,
            ])
            ->setParameter('startsAt', \DateTimeImmutable::createFromInterface($startsAt), Types::DATETIME_IMMUTABLE)
            ->setParameter('endsAt', \DateTimeImmutable::createFromInterface($endsAt), Types::DATETIME_IMMUTABLE);

        if ($excludeAppointmentId !== null) {
            $queryBuilder
                ->andWhere('a.id != :excludeAppointmentId')
                ->setParameter('excludeAppointmentId', $excludeAppointmentId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * @param list<AppointmentStatus> $statuses
     */
    public function createPatientListQueryBuilder(User $patient, array $statuses = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('m', 'doctorUser', 'specialty', 'consultation')
            ->join('a.medecin', 'm')
            ->join('m.user', 'doctorUser')
            ->join('m.specialty', 'specialty')
            ->leftJoin('a.consultation', 'consultation')
            ->andWhere('a.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('a.dateTime', 'DESC');

        if ($statuses !== []) {
            $queryBuilder
                ->andWhere('a.status IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }

        return $queryBuilder;
    }

    public function findOneForPatientById(User $patient, int $appointmentId): ?Appointment
    {
        return $this->createPatientListQueryBuilder($patient)
            ->andWhere('a.id = :appointmentId')
            ->setParameter('appointmentId', $appointmentId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createAdminListQueryBuilder(
        ?AppointmentStatus $status = null,
        ?\DateTimeInterface $date = null,
        ?int $medecinId = null,
    ): QueryBuilder {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('patient', 'm', 'doctorUser', 'specialty', 'validatedBy', 'consultation')
            ->join('a.patient', 'patient')
            ->join('a.medecin', 'm')
            ->join('m.user', 'doctorUser')
            ->join('m.specialty', 'specialty')
            ->leftJoin('a.validatedBy', 'validatedBy')
            ->leftJoin('a.consultation', 'consultation')
            ->orderBy('a.dateTime', 'DESC');

        if ($status !== null) {
            $queryBuilder
                ->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        if ($date !== null) {
            $normalizedDate = \DateTimeImmutable::createFromInterface($date);

            $queryBuilder
                ->andWhere('a.dateTime BETWEEN :dayStart AND :dayEnd')
                ->setParameter('dayStart', $normalizedDate->setTime(0, 0, 0), Types::DATETIME_IMMUTABLE)
                ->setParameter('dayEnd', $normalizedDate->setTime(23, 59, 59), Types::DATETIME_IMMUTABLE);
        }

        if ($medecinId !== null) {
            $queryBuilder
                ->andWhere('m.id = :medecinId')
                ->setParameter('medecinId', $medecinId);
        }

        return $queryBuilder;
    }

    public function findOneForAdminById(int $appointmentId): ?Appointment
    {
        return $this->createAdminListQueryBuilder()
            ->andWhere('a.id = :appointmentId')
            ->setParameter('appointmentId', $appointmentId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param list<AppointmentStatus> $statuses
     *
     * @return list<Appointment>
     */
    public function findBetweenForMedecin(
        MedecinProfile $medecin,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        array $statuses = [],
    ): array {
        return $this->createMedecinScopeQueryBuilder($medecin, $statuses)
            ->andWhere('a.dateTime BETWEEN :from AND :to')
            ->setParameter('from', \DateTimeImmutable::createFromInterface($from), Types::DATETIME_IMMUTABLE)
            ->setParameter('to', \DateTimeImmutable::createFromInterface($to), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getResult();
    }

    public function findOneForMedecinById(MedecinProfile $medecin, int $appointmentId): ?Appointment
    {
        return $this->createMedecinScopeQueryBuilder($medecin)
            ->andWhere('a.id = :appointmentId')
            ->setParameter('appointmentId', $appointmentId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Appointment>
     */
    public function findHistoryForMedecinPatient(MedecinProfile $medecin, User $patient): array
    {
        return $this->createMedecinScopeQueryBuilder($medecin)
            ->andWhere('a.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('a.dateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasRelationshipWithPatient(MedecinProfile $medecin, User $patient): bool
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.medecin = :medecin')
            ->andWhere('a.patient = :patient')
            ->andWhere('a.status IN (:statuses)')
            ->setParameter('medecin', $medecin)
            ->setParameter('patient', $patient)
            ->setParameter('statuses', [AppointmentStatus::CONFIRMED, AppointmentStatus::COMPLETED])
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function countDistinctPatientsForMedecin(MedecinProfile $medecin): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(DISTINCT patient.id)')
            ->join('a.patient', 'patient')
            ->andWhere('a.medecin = :medecin')
            ->andWhere('a.status IN (:statuses)')
            ->setParameter('medecin', $medecin)
            ->setParameter('statuses', [AppointmentStatus::CONFIRMED, AppointmentStatus::COMPLETED])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Appointment>
     */
    public function findUpcomingForPatient(User $patient, int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('m', 'doctorUser', 'specialty')
            ->join('a.medecin', 'm')
            ->join('m.user', 'doctorUser')
            ->join('m.specialty', 'specialty')
            ->andWhere('a.patient = :patient')
            ->andWhere('a.dateTime >= :now')
            ->andWhere('a.status IN (:statuses)')
            ->setParameter('patient', $patient)
            ->setParameter('now', new \DateTimeImmutable(), Types::DATETIME_IMMUTABLE)
            ->setParameter('statuses', [AppointmentStatus::PENDING, AppointmentStatus::CONFIRMED])
            ->orderBy('a.dateTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Appointment>
     */
    public function findRecentForAdminDashboard(int $limit = 6): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('patient', 'm', 'doctorUser', 'specialty')
            ->join('a.patient', 'patient')
            ->join('a.medecin', 'm')
            ->join('m.user', 'doctorUser')
            ->join('m.specialty', 'specialty')
            ->andWhere('a.dateTime >= :now')
            ->setParameter('now', new \DateTimeImmutable()->modify('-1 day'), Types::DATETIME_IMMUTABLE)
            ->orderBy('a.dateTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Appointment>
     */
    public function findPendingForAdminDashboard(int $limit = 6): array
    {
        return $this->createAdminListQueryBuilder(AppointmentStatus::PENDING)
            ->orderBy('a.dateTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Appointment>
     */
    public function findUpcomingForMedecinDashboard(MedecinProfile $medecin, int $limit = 8): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('patient')
            ->join('a.patient', 'patient')
            ->andWhere('a.medecin = :medecin')
            ->andWhere('a.dateTime >= :now')
            ->andWhere('a.status = :status')
            ->setParameter('medecin', $medecin)
            ->setParameter('now', new \DateTimeImmutable(), Types::DATETIME_IMMUTABLE)
            ->setParameter('status', AppointmentStatus::CONFIRMED)
            ->orderBy('a.dateTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(AppointmentStatus $status): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForDay(\DateTimeInterface $date, ?AppointmentStatus $status = null): int
    {
        $normalizedDate = \DateTimeImmutable::createFromInterface($date);
        $queryBuilder = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.dateTime BETWEEN :dayStart AND :dayEnd')
            ->setParameter('dayStart', $normalizedDate->setTime(0, 0, 0), Types::DATETIME_IMMUTABLE)
            ->setParameter('dayEnd', $normalizedDate->setTime(23, 59, 59), Types::DATETIME_IMMUTABLE);

        if ($status !== null) {
            $queryBuilder
                ->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function countForMedecin(MedecinProfile $medecin, ?AppointmentStatus $status = null): int
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.medecin = :medecin')
            ->setParameter('medecin', $medecin);

        if ($status !== null) {
            $queryBuilder
                ->andWhere('a.status = :status')
                ->setParameter('status', $status);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<Appointment>
     */
    public function findUpcomingForMedecinAdmin(MedecinProfile $medecin, int $limit = 5): array
    {
        return $this->createQueryBuilder('a')
            ->addSelect('patient')
            ->join('a.patient', 'patient')
            ->andWhere('a.medecin = :medecin')
            ->andWhere('a.dateTime >= :now')
            ->andWhere('a.status IN (:statuses)')
            ->setParameter('medecin', $medecin)
            ->setParameter('now', new \DateTimeImmutable(), Types::DATETIME_IMMUTABLE)
            ->setParameter('statuses', [AppointmentStatus::PENDING, AppointmentStatus::CONFIRMED])
            ->orderBy('a.dateTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<AppointmentStatus> $statuses
     */
    private function createMedecinScopeQueryBuilder(MedecinProfile $medecin, array $statuses = []): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('patient', 'doctorProfile', 'doctorUser', 'specialty', 'consultation')
            ->join('a.patient', 'patient')
            ->join('a.medecin', 'doctorProfile')
            ->join('doctorProfile.user', 'doctorUser')
            ->join('doctorProfile.specialty', 'specialty')
            ->leftJoin('a.consultation', 'consultation')
            ->andWhere('a.medecin = :medecin')
            ->setParameter('medecin', $medecin)
            ->orderBy('a.dateTime', 'ASC');

        if ($statuses !== []) {
            $queryBuilder
                ->andWhere('a.status IN (:statuses)')
                ->setParameter('statuses', $statuses);
        }

        return $queryBuilder;
    }
}
