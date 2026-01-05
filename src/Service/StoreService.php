<?php

namespace App\Service;

use App\Entity\Store;
use App\Entity\StoreSetting;
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
        // Initialize StoreSetting if not already set
        if (!$store->getSetting()) {
            $storeSetting = new StoreSetting();
            $store->setSetting($storeSetting);
            
            // Persist StoreSetting and its BusinessHours
            $this->manager->persist($storeSetting);
            $this->persistBusinessHoursIfNeeded($storeSetting);
        }
        
        $this->manager->persist($store);
        $this->manager->flush();
    }

    private function persistBusinessHoursIfNeeded(StoreSetting $setting): void
    {
        // Persist any BusinessHours that haven't been persisted yet
        // Note: All BusinessHours are null by default, so this will only persist
        // BusinessHours that have been explicitly set
        $methods = [
            'getMondayHours',
            'getTuesdayHours',
            'getWednesdayHours',
            'getThursdayHours',
            'getFridayHours',
            'getSaturdayHours',
            'getSundayHours'
        ];

        foreach ($methods as $method) {
            $hours = $setting->$method();
            if ($hours && $hours->getId() === null) {
                $this->manager->persist($hours);
            }
        }
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
        $this->manager->persist($store);
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
        // First, handle StoreSetting separately to avoid cascade cycle issues
        $setting = $store->getSetting();
        if ($setting) {
            // Clear all BusinessHours references before removing StoreSetting
            $setting->setMondayHours(null);
            $setting->setTuesdayHours(null);
            $setting->setWednesdayHours(null);
            $setting->setThursdayHours(null);
            $setting->setFridayHours(null);
            $setting->setSaturdayHours(null);
            $setting->setSundayHours(null);
            
            // Flush to clear the references
            $this->manager->flush();
            
            // Remove StoreSetting explicitly (before removing Store)
            $store->setSetting(null);
            $this->manager->remove($setting);
            $this->manager->flush();
        }
        
        // Handle StoreProducts (they have foreign key to Store)
        foreach ($store->getStoreProducts() as $storeProduct) {
            $this->manager->remove($storeProduct);
        }
        $this->manager->flush();
        
        // Handle owner relationship (clear the bidirectional reference)
        $owner = $store->getOwner();
        if ($owner) {
            // Clear the Store reference from User to break the cycle
            // We don't delete the User, just remove the Store reference
            $store->setOwner(null);
            $this->manager->flush();
        }
        
        // Now remove the Store (without cascade, to avoid cycles)
        $this->manager->remove($store);
        $this->manager->flush();
    }
}
