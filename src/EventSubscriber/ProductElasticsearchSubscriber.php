<?php

namespace App\EventSubscriber;

use App\Entity\Product;
use App\Service\ProductElasticsearchService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Product::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Product::class)]
#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Product::class)]
class ProductElasticsearchSubscriber
{
    public function __construct(
        private readonly ProductElasticsearchService $productElasticsearchService
    ) {
    }

    public function postPersist(Product $product): void
    {
        try {
            $this->productElasticsearchService->indexProduct($product);
        } catch (\Exception $e) {
            // Log error but don't break the application
            // In production, you might want to use a queue system
            error_log('Failed to index product in Elasticsearch: ' . $e->getMessage());
        }
    }

    public function postUpdate(Product $product): void
    {
        try {
            $this->productElasticsearchService->updateProduct($product);
        } catch (\Exception $e) {
            // Log error but don't break the application
            error_log('Failed to update product in Elasticsearch: ' . $e->getMessage());
        }
    }

    public function preRemove(Product $product): void
    {
        try {
            $this->productElasticsearchService->deleteProduct($product->getId());
        } catch (\Exception $e) {
            // Log error but don't break the application
            error_log('Failed to delete product from Elasticsearch: ' . $e->getMessage());
        }
    }
}

