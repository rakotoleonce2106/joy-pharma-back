<?php

namespace App\Service;

use App\Entity\Store;
use App\Repository\StoreRepository;
use Doctrine\ORM\EntityManagerInterface;


readonly class StoreService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private StoreRepository $storeRepository
    )
    {
    }

    public function createStore(Store $store): void
    {
        $this->manager->persist($store);
        $this->manager->flush();
    }


    public function getOrCreateStoreByName(string $name): Store
    {
        $store = $this->storeRepository->findOneBy(['name' => $name]);
        if (!$store) {
            $store = new Store();
            $store->setName($name);
            $this->manager->persist($store);
        }
        return $store;
    }


    public function updateStore(Store $store): void
    {
        $this->manager->flush();
    }

    public function findByName(string $name): ?Store
    {
        return $this->manager->getRepository(Store::class)
            ->findOneBy(['name' => $name]);
    }

    public function batchDeleteStores(array $storeIds): void
    {
        foreach ($storeIds as $id) {
            $store = $this->storeRepository->find($id);
            if ($store) {
                $this->deleteStore($store);

            }
        }
    }

    public function deleteStore(Store $store): void
    {
        $this->manager->remove($store);
        $this->manager->flush();
    }
}
