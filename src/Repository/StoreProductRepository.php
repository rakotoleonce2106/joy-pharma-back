<?php

namespace App\Repository;

use App\Entity\StoreProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreProduct>
 */
class StoreProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreProduct::class);
    }

    /**
     * Find all store products for a specific store
     * 
     * @param \App\Entity\Store $store
     * @return StoreProduct[] Returns an array of StoreProduct objects
     */
    public function findByStore(\App\Entity\Store $store): array
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.store = :store')
            ->setParameter('store', $store)
            ->orderBy('sp.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find store products by store ID
     * 
     * @param int $storeId
     * @return StoreProduct[] Returns an array of StoreProduct objects
     */
    public function findByStoreId(int $storeId): array
    {
        return $this->createQueryBuilder('sp')
            ->join('sp.store', 's')
            ->andWhere('s.id = :storeId')
            ->setParameter('storeId', $storeId)
            ->orderBy('sp.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
