<?php

namespace App\EventSubscriber;

use App\Entity\Product;
use App\Entity\StoreProduct;
use App\Repository\ProductRepository;
use App\Repository\StoreProductRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * EventSubscriber for StoreProduct entity lifecycle events
 * Handles:
 * - Product validation
 * - Duplicate StoreProduct validation
 * - Price and stock validation
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: StoreProduct::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: StoreProduct::class)]
class StoreProductSubscriber
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly StoreProductRepository $storeProductRepository
    ) {}

    /**
     * Validate product and check for duplicates before persisting (create)
     */
    public function prePersist(StoreProduct $storeProduct, PrePersistEventArgs $event): void
    {
        // Validate product is provided
        if (!$storeProduct->getProduct()) {
            throw new BadRequestHttpException('Product is required');
        }

        $product = $storeProduct->getProduct();
        if (!$product instanceof Product) {
            throw new BadRequestHttpException('Invalid product');
        }

        // Ensure product is persisted and fetch from repository to validate existence
        $productId = $product->getId();
        if (!$productId) {
            throw new BadRequestHttpException('Product ID is required');
        }

        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new NotFoundHttpException('Product not found');
        }

        // Validate store is provided
        if (!$storeProduct->getStore()) {
            throw new BadRequestHttpException('Store is required');
        }

        // Check if store product already exists for this store and product
        $existingStoreProduct = $this->storeProductRepository->findOneBy([
            'store' => $storeProduct->getStore(),
            'product' => $product
        ]);

        if ($existingStoreProduct) {
            throw new BadRequestHttpException('Store product already exists for this product');
        }

        // Validate required fields
        if ($storeProduct->getPrice() === null || $storeProduct->getPrice() <= 0) {
            throw new BadRequestHttpException('Price must be greater than 0');
        }

        if ($storeProduct->getStock() === null || $storeProduct->getStock() < 0) {
            throw new BadRequestHttpException('Stock must be 0 or greater');
        }
    }

    /**
     * Validate updates before updating
     */
    public function preUpdate(StoreProduct $storeProduct, PreUpdateEventArgs $event): void
    {
        // Validate price if provided
        if ($storeProduct->getPrice() !== null && $storeProduct->getPrice() <= 0) {
            throw new BadRequestHttpException('Price must be greater than 0');
        }

        // Validate stock if provided
        if ($storeProduct->getStock() !== null && $storeProduct->getStock() < 0) {
            throw new BadRequestHttpException('Stock must be 0 or greater');
        }

        // If product is being changed, check for duplicates
        if ($storeProduct->getProduct() && $storeProduct->getStore()) {
            $existingStoreProduct = $this->storeProductRepository->findOneBy([
                'store' => $storeProduct->getStore(),
                'product' => $storeProduct->getProduct()
            ]);

            // Get the original entity to check if it's the same StoreProduct
            $uow = $event->getObjectManager()->getUnitOfWork();
            $originalData = $uow->getOriginalEntityData($storeProduct);
            
            if ($existingStoreProduct && (!isset($originalData['id']) || $existingStoreProduct->getId() !== $originalData['id'])) {
                throw new BadRequestHttpException('Store product already exists for this product');
            }
        }
    }
}

