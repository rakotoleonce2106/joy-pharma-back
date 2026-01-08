<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findRecentProducts(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC') // Produits les plus récents (nouveautés)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }




    public function findByCategory(int $id, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.category', 'c')
            ->where('c.id = :categoryId')
            ->setParameter('categoryId', $id)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des produits par nom (recherche partielle insensible à la casse)
     *
     * @param string $name Le nom ou partie du nom à rechercher
     * @param int $limit Nombre maximum de résultats
     * @return Product[] Liste des produits trouvés
     */
    public function searchByName(string $name, int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE LOWER(:name)')
            ->andWhere('p.isActive = :isActive')
            ->setParameter('name', '%' . $name . '%')
            ->setParameter('isActive', true)
            ->orderBy('p.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche plusieurs produits par leurs noms
     *
     * @param array $names Liste des noms de produits à rechercher
     * @param int $limit Nombre maximum de résultats par nom
     * @return Product[] Liste des produits trouvés
     */
    public function searchByNames(array $names, int $limit = 10): array
    {
        if (empty($names)) {
            return [];
        }

        $qb = $this->createQueryBuilder('p')
            ->where('p.isActive = :isActive')
            ->setParameter('isActive', true);

        $orConditions = [];
        foreach ($names as $index => $name) {
            $paramName = 'name_' . $index;
            $orConditions[] = 'LOWER(p.name) LIKE LOWER(:' . $paramName . ')';
            $qb->setParameter($paramName, '%' . trim($name) . '%');
        }

        if (!empty($orConditions)) {
            $qb->andWhere(implode(' OR ', $orConditions));
        }

        return $qb
            ->orderBy('p.name', 'ASC')
            ->setMaxResults($limit * count($names))
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
