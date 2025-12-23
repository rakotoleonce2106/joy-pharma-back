<?php

namespace App\EventSubscriber;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\MediaObjectService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * EventSubscriber for Product entity lifecycle events
 * Handles:
 * - Code uniqueness validation
 * - Orphan image cleanup
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Product::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Product::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Product::class)]
class ProductSubscriber
{
    private array $previousImageIds = [];

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly MediaObjectService $mediaObjectService
    ) {
    }

    /**
     * Validate code uniqueness before persisting (create)
     */
    public function prePersist(Product $product, PrePersistEventArgs $event): void
    {
        if ($product->getCode()) {
            $existingProduct = $this->productRepository->findOneBy(['code' => $product->getCode()]);
            if ($existingProduct) {
                throw new BadRequestHttpException('Product with this code already exists');
            }
        }
    }

    /**
     * Store previous image IDs and protect required fields from being set to null
     */
    public function preUpdate(Product $product, PreUpdateEventArgs $event): void
    {
        // Get the original entity from the unit of work
        $uow = $event->getObjectManager()->getUnitOfWork();
        $originalData = $uow->getOriginalEntityData($product);
        
        // If we have the original entity, get its images and protect required fields
        if (isset($originalData['id'])) {
            $originalProduct = $event->getObjectManager()->find(Product::class, $originalData['id']);
            if ($originalProduct) {
                $this->previousImageIds = $originalProduct->getImages()->map(fn($img) => $img->getId())->toArray();
                
                // Protect required fields from being set to null during partial updates
                // If name is null but was not null before, restore the original value
                if ($product->getName() === null && $originalProduct->getName() !== null) {
                    $product->setName($originalProduct->getName());
                }
                
                // Code can be nullable in DB but we still protect it if it was set before
                if ($product->getCode() === null && $originalProduct->getCode() !== null) {
                    $product->setCode($originalProduct->getCode());
                }
            }
        } else {
            // Fallback: get from current product (may not have changes yet)
            $this->previousImageIds = $product->getImages()->map(fn($img) => $img->getId())->toArray();
        }
        
        // Final validation: ensure name is never null (required field)
        if ($product->getName() === null) {
            throw new BadRequestHttpException('Product name cannot be null');
        }
    }

    /**
     * Clean up orphan images after update
     */
    public function postUpdate(Product $product, PostUpdateEventArgs $event): void
    {
        // Get current image IDs
        $currentImageIds = $product->getImages()->map(fn($img) => $img->getId())->toArray();
        
        // Find images that were removed
        $idsToDelete = array_diff($this->previousImageIds, $currentImageIds);
        
        if (!empty($idsToDelete)) {
            $this->mediaObjectService->deleteMediaObjectsByIds($idsToDelete);
        }
    }
}

