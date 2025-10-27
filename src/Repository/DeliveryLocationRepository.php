<?php

namespace App\Repository;

use App\Entity\DeliveryLocation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeliveryLocation>
 */
class DeliveryLocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryLocation::class);
    }

    public function save(DeliveryLocation $location, bool $flush = false): void
    {
        $this->getEntityManager()->persist($location);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getLastLocationForDeliveryPerson(User $deliveryPerson): ?DeliveryLocation
    {
        return $this->createQueryBuilder('dl')
            ->where('dl.deliveryPerson = :deliveryPerson')
            ->setParameter('deliveryPerson', $deliveryPerson)
            ->orderBy('dl.timestamp', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}


