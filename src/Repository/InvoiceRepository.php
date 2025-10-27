<?php

namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    public function save(Invoice $invoice, bool $flush = false): void
    {
        $this->getEntityManager()->persist($invoice);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByDeliveryPerson(User $deliveryPerson, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('i')
            ->where('i.deliveryPerson = :deliveryPerson')
            ->setParameter('deliveryPerson', $deliveryPerson)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}


