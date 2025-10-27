<?php

namespace App\Repository;

use App\Entity\DeliverySchedule;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeliverySchedule>
 */
class DeliveryScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliverySchedule::class);
    }

    public function save(DeliverySchedule $schedule, bool $flush = false): void
    {
        $this->getEntityManager()->persist($schedule);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DeliverySchedule $schedule, bool $flush = false): void
    {
        $this->getEntityManager()->remove($schedule);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByDeliveryPerson(User $deliveryPerson): array
    {
        return $this->createQueryBuilder('ds')
            ->where('ds.deliveryPerson = :deliveryPerson')
            ->setParameter('deliveryPerson', $deliveryPerson)
            ->orderBy('ds.dayOfWeek', 'ASC')
            ->addOrderBy('ds.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}


