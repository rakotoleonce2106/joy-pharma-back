<?php

namespace App\State\Store;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\StoreRepository;

class StoreCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly StoreRepository $storeRepository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $page = max(1, (int) ($context['filters']['page'] ?? 1));
        $limit = min(50, max(1, (int) ($context['filters']['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        // Get all active stores
        return $this->storeRepository->createQueryBuilder('s')
            ->where('s.active = :status')
            ->setParameter('status', true) // Assuming true is active status
            ->orderBy('s.name', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}






