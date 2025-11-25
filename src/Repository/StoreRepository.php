<?php

namespace App\Repository;

use App\Entity\Store;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Store>
 */
class StoreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Store::class);
    }

    public function findAllWithLocations(): array
    {
        $results = $this->createQueryBuilder('s')
            ->select('s.id AS id', 's.name AS name', 'l.latitude AS latitude', 'l.longitude AS longitude', 'l.address AS address')
            ->leftJoin('s.location', 'l')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): array => [
                'id' => $row['id'],
                'name' => $row['name'],
                'location' => $row['latitude'] !== null && $row['longitude'] !== null ? [
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'address' => $row['address'],
                ] : null,
            ],
            $results
        );
    }

    //    /**
    //     * @return Store[] Returns an array of Store objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Store
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
