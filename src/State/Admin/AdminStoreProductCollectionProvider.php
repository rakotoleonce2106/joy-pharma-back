<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\StoreProduct;
use App\Repository\StoreProductRepository;
use App\Repository\StoreRepository;

/**
 * Provider for admin store product collection with store_id filtering
 */
class AdminStoreProductCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly StoreProductRepository $storeProductRepository,
        private readonly StoreRepository $storeRepository
    ) {}

    /**
     * @return StoreProduct[]
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
            
            return $this->storeProductRepository->findBy(
                ['store' => $store],
                ['id' => 'DESC']
            );
        }
        
        // Return all store products if no store_id filter
        return $this->storeProductRepository->findBy([], ['id' => 'DESC']);
    }
}
