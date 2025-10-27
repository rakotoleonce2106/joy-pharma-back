<?php

namespace App\Repository;

use App\Entity\Rating;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rating>
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    public function save(Rating $rating, bool $flush = false): void
    {
        $this->getEntityManager()->persist($rating);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getAverageRatingForDeliveryPerson(User $deliveryPerson): ?float
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avgRating')
            ->where('r.deliveryPerson = :deliveryPerson')
            ->setParameter('deliveryPerson', $deliveryPerson)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }
}


