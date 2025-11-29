<?php

namespace App\Repository;

use App\Entity\ProductPromotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductPromotion>
 */
class ProductPromotionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductPromotion::class);
    }

    /**
     * Find active promotion for a product
     */
    public function findActiveForProduct(int $productId): ?ProductPromotion
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('pp')
            ->where('pp.product = :productId')
            ->andWhere('pp.isActive = :isActive')
            ->andWhere('(pp.startDate IS NULL OR pp.startDate <= :now)')
            ->andWhere('(pp.endDate IS NULL OR pp.endDate >= :now)')
            ->setParameter('productId', $productId)
            ->setParameter('isActive', true)
            ->setParameter('now', $now)
            ->orderBy('pp.discountPercentage', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all active promotions for a product
     */
    public function findAllActiveForProduct(int $productId): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('pp')
            ->where('pp.product = :productId')
            ->andWhere('pp.isActive = :isActive')
            ->andWhere('(pp.startDate IS NULL OR pp.startDate <= :now)')
            ->andWhere('(pp.endDate IS NULL OR pp.endDate >= :now)')
            ->setParameter('productId', $productId)
            ->setParameter('isActive', true)
            ->setParameter('now', $now)
            ->orderBy('pp.discountPercentage', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

