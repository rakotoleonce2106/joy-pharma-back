<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\OrderStatus;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $order, bool $flush = false): void
    {
        $this->getEntityManager()->persist($order);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find available orders for delivery (pending, not assigned to any delivery person)
     */
    public function findAvailableOrders(int $limit = 10, int $offset = 0): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->andWhere('o.deliver IS NULL')
            ->setParameter('status', OrderStatus::STATUS_PENDING)
            ->orderBy('o.priority', 'DESC')
            ->addOrderBy('o.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all available orders for delivery (no pagination)
     */
    public function findAllAvailableOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status = :status')
            ->andWhere('o.deliver IS NULL')
            ->setParameter('status', OrderStatus::STATUS_PENDING)
            ->orderBy('o.priority', 'DESC')
            ->addOrderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find current active order for a delivery person
     * Includes orders that are assigned to the delivery person and have status:
     * - pending (if assigned, means accepted but status not yet updated)
     * - confirmed, processing, collected, or shipped (active delivery states)
     */
    public function findCurrentOrderForDeliveryPerson(User $deliveryPerson): ?Order
    {
        return $this->createQueryBuilder('o')
            ->where('o.deliver = :deliveryPerson')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('deliveryPerson', $deliveryPerson)
            ->setParameter('statuses', [
                OrderStatus::STATUS_PENDING,    // Include pending if assigned (means accepted)
                OrderStatus::STATUS_CONFIRMED,
                OrderStatus::STATUS_PROCESSING,
                OrderStatus::STATUS_COLLECTED,  // Order collected from store
                OrderStatus::STATUS_SHIPPED
            ])
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find order history for a delivery person
     */
    public function findOrderHistoryForDeliveryPerson(
        User $deliveryPerson,
        int $limit = 20,
        int $offset = 0,
        ?string $status = null
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->where('o.deliver = :deliveryPerson')
            ->setParameter('deliveryPerson', $deliveryPerson);

        if ($status) {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total deliveries for a delivery person
     */
    public function countDeliveriesForPerson(User $deliveryPerson, ?\DateTime $startDate = null, ?\DateTime $endDate = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.deliver = :deliveryPerson')
            ->andWhere('o.status = :status')
            ->setParameter('deliveryPerson', $deliveryPerson)
            ->setParameter('status', OrderStatus::STATUS_DELIVERED);

        if ($startDate) {
            $qb->andWhere('o.deliveredAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('o.deliveredAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Calculate total earnings for a delivery person
     */
    public function calculateEarningsForPerson(User $deliveryPerson, ?\DateTime $startDate = null, ?\DateTime $endDate = null): float
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.deliveryFee)')
            ->where('o.deliver = :deliveryPerson')
            ->andWhere('o.status = :status')
            ->andWhere('o.deliveryFee IS NOT NULL')
            ->setParameter('deliveryPerson', $deliveryPerson)
            ->setParameter('status', OrderStatus::STATUS_DELIVERED);

        if ($startDate) {
            $qb->andWhere('o.deliveredAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('o.deliveredAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return (float) ($qb->getQuery()->getSingleScalarResult() ?? 0);
    }

    public function sumDeliveredTotalAmount(): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.status = :status')
            ->setParameter('status', OrderStatus::STATUS_DELIVERED)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function sumDeliveredTotalAmountSince(\DateTimeInterface $since): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.status = :status')
            ->andWhere('o.deliveredAt >= :since')
            ->setParameter('status', OrderStatus::STATUS_DELIVERED)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function sumDeliveredTotalAmountBetween(\DateTimeInterface $start, \DateTimeInterface $end): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.status = :status')
            ->andWhere('o.deliveredAt >= :start')
            ->andWhere('o.deliveredAt < :end')
            ->setParameter('status', OrderStatus::STATUS_DELIVERED)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    public function countCreatedSince(\DateTimeInterface $since): int
    {
        $result = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function findActiveOrdersWithLocation(int $limit = 50): array
    {
        return $this->createQueryBuilder('o')
            ->select('o.id AS id', 'o.reference AS reference', 'o.totalAmount AS totalAmount', 'o.status AS status', 'l.latitude AS latitude', 'l.longitude AS longitude', 'l.address AS address')
            ->leftJoin('o.location', 'l')
            ->where('o.status IN (:statuses)')
            ->andWhere('l.latitude IS NOT NULL')
            ->andWhere('l.longitude IS NOT NULL')
            ->setParameter('statuses', [
                OrderStatus::STATUS_PENDING,
                OrderStatus::STATUS_CONFIRMED,
                OrderStatus::STATUS_PROCESSING,
                OrderStatus::STATUS_SHIPPED,
            ])
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }

    public function findRecentOrders(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Order[] Returns an array of Order objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Order
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
