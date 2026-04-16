<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Medication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Medication>
 */
final class MedicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Medication::class);
    }

    /**
     * @return list<Medication>
     */
    public function findActiveCatalog(?string $search = null): array
    {
        $queryBuilder = $this->createQueryBuilder('m')
            ->andWhere('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.name', 'ASC');

        if ($search !== null && trim($search) !== '') {
            $queryBuilder
                ->andWhere('LOWER(m.name) LIKE :search OR LOWER(COALESCE(m.genericName, \'\')) LIKE :search OR LOWER(COALESCE(m.category, \'\')) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower(trim($search)).'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
