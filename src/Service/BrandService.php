<?php

namespace App\Service;

use App\Entity\Brand;
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

    public function batchDeleteBrands(array $brandIds): void
    {


        foreach ($brandIds as $id) {
            $brand = $this->brandRepository->find($id);
            if ($brand) {
                $this->deleteBrand($brand);

            }
        }


    }

    public function deleteBrand(Brand $brand): void
    {
        $this->manager->remove($brand);
        $this->manager->flush();
    }
}
