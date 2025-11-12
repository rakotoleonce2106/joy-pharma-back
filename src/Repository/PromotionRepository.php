<?php

namespace App\Repository;

use App\Entity\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
 */
class PromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    /**
     * Find promotion by code
     */
    public function findByCode(string $code): ?Promotion
    {
        return $this->createQueryBuilder('p')
            ->where('p.code = :code')
            ->setParameter('code', strtoupper($code))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find active promotions
     */
    public function findActive(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('p')
            ->where('p.isActive = :isActive')
            ->andWhere('(p.startDate IS NULL OR p.startDate <= :now)')
            ->andWhere('(p.endDate IS NULL OR p.endDate >= :now)')
            ->andWhere('(p.usageLimit IS NULL OR p.usageCount < p.usageLimit)')
            ->setParameter('isActive', true)
            ->setParameter('now', $now)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find valid promotion by code
     */
    public function findValidByCode(string $code): ?Promotion
    {
        $promotion = $this->findByCode($code);
        
        if ($promotion && $promotion->isValid()) {
            return $promotion;
        }

        return null;
    }
}

