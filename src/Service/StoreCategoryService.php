<?php

namespace App\Service;

use App\Entity\StoreCategory;
use App\Entity\MediaFile;
use App\Entity\Store;
use App\Repository\StoreCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;


readonly class StoreCategoryService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private StoreCategoryRepository $storeCategoryRepository
    ) {
    }

    public function createStoreCategory(StoreCategory $storeCategory): void
    {
        $this->manager->persist($storeCategory);
        $this->manager->flush();
    }


 public function getOrCreateStoteCategoryByName(string $name): StoreCategory
    {
        $storeCategory = $this->storeCategoryRepository->findOneBy(['label' => $name]);
        if (!$storeCategory) {
            $storeCategory = new StoreCategory();
            $storeCategory->setLabel($name);
            $this->manager->persist($storeCategory);
        }
        return $storeCategory;
    }

    public function findParentCategories(): array
    {
      return  $this->storeCategoryRepository->findRootCategories();
    }

    public function updateStoreCategory(StoreCategory $storeCategory): void
    {
        $this->manager->flush();
    }


    public function findByName(String $name): ?StoreCategory
    {
        return $this->manager->getRepository(StoreCategory::class)
            ->findOneBy(['name' => $name]);
    }

    public function deleteStoreCategory(StoreCategory $storeCategory): void
    {
        $this->manager->remove($storeCategory);
        $this->manager->flush();
    }

    public function batchDeleteCategories(array $storeCategoryIds): void
    {
        foreach ($storeCategoryIds as $id) {
            $storeCategory = $this->storeCategoryRepository->find($id);
            if ($storeCategory) {
                $this->deleteStoreCategory($storeCategory);

            }
        }
    }
}
