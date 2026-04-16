<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Consultation;
use App\Entity\MedecinProfile;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consultation>
 */
final class ConsultationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consultation::class);
    }

    /**
     * @return list<Consultation>
     */
    public function findCompletedForPatient(User $patient): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('a', 'doctorProfile', 'doctorUser', 'prescription', 'items', 'medication')
            ->join('c.appointment', 'a')
            ->join('a.medecin', 'doctorProfile')
            ->join('doctorProfile.user', 'doctorUser')
            ->leftJoin('c.prescription', 'prescription')
            ->leftJoin('prescription.items', 'items')
            ->leftJoin('items.medication', 'medication')
            ->andWhere('a.patient = :patient')
            ->andWhere('c.isCompleted = :completed')
            ->setParameter('patient', $patient)
            ->setParameter('completed', true)
            ->orderBy('c.completedAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneCompletedForPatientById(User $patient, int $consultationId): ?Consultation
    {
        return $this->createQueryBuilder('c')
            ->addSelect('a', 'doctorProfile', 'doctorUser', 'prescription', 'items', 'medication')
            ->join('c.appointment', 'a')
            ->join('a.medecin', 'doctorProfile')
            ->join('doctorProfile.user', 'doctorUser')
            ->leftJoin('c.prescription', 'prescription')
            ->leftJoin('prescription.items', 'items')
            ->leftJoin('items.medication', 'medication')
            ->andWhere('c.id = :consultationId')
            ->andWhere('a.patient = :patient')
            ->andWhere('c.isCompleted = :completed')
            ->setParameter('consultationId', $consultationId)
            ->setParameter('patient', $patient)
            ->setParameter('completed', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneForMedecinById(MedecinProfile $medecin, int $consultationId): ?Consultation
    {
        return $this->createQueryBuilder('c')
            ->addSelect('a', 'patient', 'doctorProfile', 'doctorUser', 'specialty', 'prescription', 'items', 'medication')
            ->join('c.appointment', 'a')
            ->join('a.patient', 'patient')
            ->join('a.medecin', 'doctorProfile')
            ->join('doctorProfile.user', 'doctorUser')
            ->join('doctorProfile.specialty', 'specialty')
            ->leftJoin('c.prescription', 'prescription')
            ->leftJoin('prescription.items', 'items')
            ->leftJoin('items.medication', 'medication')
            ->andWhere('c.id = :consultationId')
            ->andWhere('a.medecin = :medecin')
            ->setParameter('consultationId', $consultationId)
            ->setParameter('medecin', $medecin)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
