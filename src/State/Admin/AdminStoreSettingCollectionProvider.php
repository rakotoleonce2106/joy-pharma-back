<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\StoreSetting;
use App\Repository\StoreSettingRepository;
use App\Repository\StoreRepository;

/**
 * Provider for admin store setting collection with store_id filtering
 */
class AdminStoreSettingCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly StoreSettingRepository $storeSettingRepository,
        private readonly StoreRepository $storeRepository
    ) {}

    /**
     * @return StoreSetting[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $filters = $context['filters'] ?? [];
        
        // Check for store_id filter
        if (isset($filters['store_id']) && is_numeric($filters['store_id'])) {
            $storeId = (int) $filters['store_id'];
            $store = $this->storeRepository->find($storeId);
            
            if (!$store) {
                return [];
            }
            
            // Get the setting from the store
            $setting = $store->getSetting();
            
            if ($setting) {
                return [$setting];
            }
            
            return [];
        }
        
        // Return all store settings if no store_id filter
        return $this->storeSettingRepository->findBy([], ['id' => 'DESC']);
    }
}
