<?php

namespace App\Repository;

use App\Entity\EmergencySOS;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmergencySOS>
 */
class EmergencySOSRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmergencySOS::class);
    }

    public function save(EmergencySOS $sos, bool $flush = false): void
    {
        $this->getEntityManager()->persist($sos);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}


