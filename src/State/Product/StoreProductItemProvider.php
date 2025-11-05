<?php

namespace App\State\Product;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\StoreProduct;
use App\Entity\User;
use App\Repository\StoreProductRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreProductItemProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly StoreProductRepository $storeProductRepository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?StoreProduct
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('User not authenticated');
        }

        if (!$this->security->isGranted('ROLE_STORE')) {
            throw new AccessDeniedHttpException('User must be a store owner');
        }

        if (!$user->getStore()) {
            throw new NotFoundHttpException('User does not have a store');
        }

        // Get the store product ID from URI variables
        $storeProductId = $uriVariables['id'] ?? null;
        if (!$storeProductId) {
            throw new NotFoundHttpException('Store product ID is required');
        }

        // Find the store product
        $storeProduct = $this->storeProductRepository->find($storeProductId);
        if (!$storeProduct) {
            throw new NotFoundHttpException('Store product not found');
        }

        // Verify the store product belongs to the user's store
        if ($storeProduct->getStore() !== $user->getStore()) {
            throw new AccessDeniedHttpException('You are not authorized to access this store product');
        }

        return $storeProduct;
    }
}

