<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MedecinProfile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MedecinProfile>
 */
final class MedecinProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MedecinProfile::class);
    }

    /**
     * @return list<MedecinProfile>
     */
    public function findActiveByFilters(?string $search = null, ?int $specialtyId = null): array
    {
        return $this->findForAdmin($search, $specialtyId, true);
    }

    /**
     * @return list<MedecinProfile>
     */
    public function findForAdmin(?string $search = null, ?int $specialtyId = null, ?bool $isActive = null): array
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->addSelect('u', 's')
            ->join('m.user', 'u')
            ->join('m.specialty', 's')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC');

        if ($isActive !== null) {
            $queryBuilder
                ->andWhere('u.isActive = :active')
                ->setParameter('active', $isActive);
        }

        $queryBuilder
            ->andWhere('s.isActive = :specialtyActive')
            ->setParameter('specialtyActive', true);

        if ($specialtyId !== null) {
            $queryBuilder
                ->andWhere('s.id = :specialtyId')
                ->setParameter('specialtyId', $specialtyId);
        }

        if ($search !== null && trim($search) !== '') {
            $queryBuilder
                ->andWhere('LOWER(CONCAT(u.firstName, \' \', u.lastName)) LIKE :search OR LOWER(u.email) LIKE :search OR LOWER(s.name) LIKE :search OR LOWER(COALESCE(m.officeLocation, \'\')) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower(trim($search)).'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findOneWithAvailability(int $id): ?MedecinProfile
    {
        return $this->createQueryBuilder('m')
            ->addSelect('u', 's', 'a')
            ->join('m.user', 'u')
            ->join('m.specialty', 's')
            ->leftJoin('m.availabilities', 'a', 'WITH', 'a.isActive = :active')
            ->andWhere('m.id = :id')
            ->andWhere('u.isActive = :active')
            ->andWhere('s.isActive = :active')
            ->setParameter('id', $id)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForAdmin(int $id): ?MedecinProfile
    {
        return $this->createQueryBuilder('m')
            ->addSelect('u', 's', 'a')
            ->join('m.user', 'u')
            ->join('m.specialty', 's')
            ->leftJoin('m.availabilities', 'a', 'WITH', 'a.isActive = :active')
            ->andWhere('m.id = :id')
            ->setParameter('id', $id)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countAll(?bool $isActive = null): int
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->join('m.user', 'u')
            ->join('m.specialty', 's')
            ->andWhere('s.isActive = :specialtyActive')
            ->setParameter('specialtyActive', true);

        if ($isActive !== null) {
            $queryBuilder
                ->andWhere('u.isActive = :active')
                ->setParameter('active', $isActive);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
