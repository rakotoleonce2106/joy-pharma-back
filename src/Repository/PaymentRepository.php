<?php

namespace App\Repository;
// Updated PaymentRepository to work with enums

use App\Entity\Payment;
use App\Entity\PaymentMethod;
use App\Entity\PaymentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }


    public function findByStatus(PaymentStatus $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByMethod(PaymentMethod $method): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.method = :method')
            ->setParameter('method', $method)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingPayments(): array
    {
        return $this->findByStatus(PaymentStatus::STATUS_PENDING);
    }

    public function findCompletedPayments(): array
    {
        return $this->findByStatus(PaymentStatus::STATUS_COMPLETED);
    }

    public function findFailedPayments(): array
    {
        return $this->findByStatus(PaymentStatus::STATUS_FAILED);
    }

    public function findPaymentsByStatuses(array $statuses): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentPayments(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPaymentsByMethodAndStatus(PaymentMethod $method, PaymentStatus $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.method = :method')
            ->andWhere('p.status = :status')
            ->setParameter('method', $method)
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalAmountByStatus(PaymentStatus $status): float
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount) as total')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
