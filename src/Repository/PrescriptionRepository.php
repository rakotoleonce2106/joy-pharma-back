<?php

namespace App\Repository;

use App\Entity\Prescription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prescription>
 */
class PrescriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prescription::class);
    }

    /**
     * Trouve les prescriptions d'un utilisateur
     */
    public function findByUser(int $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les prescriptions récentes
     */
    public function findRecentPrescriptions(int $limit = 20): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les prescriptions avec un produit spécifique
     */
    public function findByProduct(int $productId): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.products', 'prod')
            ->where('prod.id = :productId')
            ->setParameter('productId', $productId)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}