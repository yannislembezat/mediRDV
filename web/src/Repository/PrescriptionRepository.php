<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Consultation;
use App\Entity\Prescription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prescription>
 */
final class PrescriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prescription::class);
    }

    public function findOneByConsultation(Consultation $consultation): ?Prescription
    {
        return $this->createQueryBuilder('p')
            ->addSelect('items', 'medication')
            ->leftJoin('p.items', 'items')
            ->leftJoin('items.medication', 'medication')
            ->andWhere('p.consultation = :consultation')
            ->setParameter('consultation', $consultation)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
