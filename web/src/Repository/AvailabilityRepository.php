<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Availability;
use App\Entity\MedecinProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Availability>
 */
final class AvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Availability::class);
    }

    /**
     * @return list<Availability>
     */
    public function findActiveForMedecin(MedecinProfile $medecin): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.medecin = :medecin')
            ->andWhere('a.isActive = :active')
            ->setParameter('medecin', $medecin)
            ->setParameter('active', true)
            ->orderBy('a.isRecurring', 'DESC')
            ->addOrderBy('a.dayOfWeek', 'ASC')
            ->addOrderBy('a.specificDate', 'ASC')
            ->addOrderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Availability>
     */
    public function findActiveForMedecinOnDate(MedecinProfile $medecin, \DateTimeInterface $date): array
    {
        $dayOfWeek = (int) $date->format('N') - 1;

        return $this->createQueryBuilder('a')
            ->andWhere('a.medecin = :medecin')
            ->andWhere('a.isActive = :active')
            ->andWhere('
                (a.isRecurring = true AND a.dayOfWeek = :dayOfWeek)
                OR
                (a.isRecurring = false AND a.specificDate = :specificDate)
            ')
            ->setParameter('medecin', $medecin)
            ->setParameter('active', true)
            ->setParameter('dayOfWeek', $dayOfWeek)
            ->setParameter('specificDate', \DateTimeImmutable::createFromInterface($date), Types::DATE_IMMUTABLE)
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Availability>
     */
    public function findForAdminCalendar(?MedecinProfile $medecin = null): array
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->addSelect('m', 'u', 's')
            ->join('a.medecin', 'm')
            ->join('m.user', 'u')
            ->join('m.specialty', 's')
            ->andWhere('a.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->addOrderBy('a.isRecurring', 'DESC')
            ->addOrderBy('a.dayOfWeek', 'ASC')
            ->addOrderBy('a.specificDate', 'ASC')
            ->addOrderBy('a.startTime', 'ASC');

        if ($medecin !== null) {
            $queryBuilder
                ->andWhere('a.medecin = :medecin')
                ->setParameter('medecin', $medecin);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneForAdminById(int $availabilityId): ?Availability
    {
        return $this->createQueryBuilder('a')
            ->addSelect('m', 'u', 's')
            ->join('a.medecin', 'm')
            ->join('m.user', 'u')
            ->join('m.specialty', 's')
            ->andWhere('a.id = :availabilityId')
            ->setParameter('availabilityId', $availabilityId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
