<?php

namespace App\Service;

use App\Entity\Brand;
use App\Entity\Product;
use App\Repository\BrandRepository;
use Doctrine\ORM\EntityManagerInterface;


readonly class BrandService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private BrandRepository        $brandRepository
    )
    {
    }

    public function createBrand(Brand $Brand): void
    {
        $this->manager->persist($Brand);
        $this->manager->flush();
    }


    public function getOrCreateBrandByName(string $name): Brand
    {
        $Brand = $this->brandRepository->findOneBy(['name' => $name]);
        if (!$Brand) {
            $Brand = new Brand();
            $Brand->setName($name);
            $this->manager->persist($Brand);
        }
        return $Brand;
    }


    public function updateBrand(Brand $Brand): void
    {
        $this->manager->flush();
    }

    public function findByName(string $name): ?Brand
    {
        return $this->manager->getRepository(Brand::class)
            ->findOneBy(['name' => $name]);
    }

    public function findAll(): array
    {
        return $this->manager->getRepository(Brand::class)
            ->findAll();
    }

    public function batchDeleteBrands(array $brandIds): array
    {
        $successCount = 0;
        $failureCount = 0;

        if (empty($brandIds)) {
            return [
                'success_count' => 0,
                'failure_count' => 0
            ];
        }

        foreach ($brandIds as $id) {
            try {
                $brand = $this->brandRepository->find($id);
                if ($brand) {
                    $this->deleteBrandRelations($brand);
                    $this->manager->remove($brand);
                    $successCount++;
                } else {
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $failureCount++;
                error_log('Failed to delete brand with id ' . $id . ': ' . $e->getMessage());
            }
        }

        try {
            $this->manager->flush();
        } catch (\Exception $e) {
            error_log('Failed to flush brand deletions: ' . $e->getMessage());
            throw $e;
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount
        ];
    }

    public function deleteBrand(Brand $brand): void
    {
        $this->deleteBrandRelations($brand);
        $this->manager->remove($brand);
        $this->manager->flush();
    }

    /**
     * Remove all product associations before deleting the brand
     */
    private function deleteBrandRelations(Brand $brand): void
    {
        // Find all products with this brand and set brand to null
        $products = $this->manager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->where('p.brand = :brand')
            ->setParameter('brand', $brand)
            ->getQuery()
            ->getResult();

        foreach ($products as $product) {
            $product->setBrand(null);
        }
    }
}
