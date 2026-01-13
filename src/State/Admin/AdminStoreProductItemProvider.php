<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\StoreProduct;
use App\Repository\StoreProductRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provider for admin single store product retrieval
 */
class AdminStoreProductItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly StoreProductRepository $storeProductRepository
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?StoreProduct
    {
        if (!isset($uriVariables['id'])) {
            throw new NotFoundHttpException('Store product ID is required');
        }

        $storeProduct = $this->storeProductRepository->find($uriVariables['id']);

        if (!$storeProduct) {
            throw new NotFoundHttpException('Store product not found');
        }

        return $storeProduct;
    }
}
