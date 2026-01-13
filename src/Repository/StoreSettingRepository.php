<?php

namespace App\Repository;

use App\Entity\StoreSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StoreSetting>
 */
class StoreSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreSetting::class);
    }

    /**
     * Find the store setting for a specific store by store ID
     * 
     * @param int $storeId
     * @return StoreSetting|null
     */
    public function findByStoreId(int $storeId): ?StoreSetting
    {
        $result = $this->getEntityManager()
            ->createQuery(
                'SELECT ss FROM App\Entity\StoreSetting ss
                 JOIN App\Entity\Store s WITH s.setting = ss
                 WHERE s.id = :storeId'
            )
            ->setParameter('storeId', $storeId)
            ->getOneOrNullResult();
            
        return $result;
    }
}
