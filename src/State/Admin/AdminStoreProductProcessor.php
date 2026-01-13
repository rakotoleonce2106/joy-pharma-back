<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\StoreProduct;
use App\Repository\StoreProductRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Processor for admin store product create/update operations
 */
class AdminStoreProductProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly StoreProductRepository $storeProductRepository,
        private readonly ProductRepository $productRepository,
        private readonly StoreRepository $storeRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StoreProduct
    {
        if (!$data instanceof StoreProduct) {
            throw new BadRequestHttpException('Invalid data provided');
        }

        $isUpdate = $operation instanceof Put || $operation instanceof Patch;
        $isPartialUpdate = $operation instanceof Patch;

        // For updates, get the existing entity
        if ($isUpdate) {
            $existingStoreProduct = $this->storeProductRepository->find($uriVariables['id']);
            if (!$existingStoreProduct) {
                throw new NotFoundHttpException('Store product not found');
            }
            
            // For PUT, ensure all required fields are provided
            if (!$isPartialUpdate) {
                if ($data->getProduct() === null) {
                    throw new BadRequestHttpException('product is required for PUT operation');
                }
                if ($data->getStore() === null) {
                    throw new BadRequestHttpException('store is required for PUT operation');
                }
                if ($data->getPrice() === null) {
                    throw new BadRequestHttpException('price is required for PUT operation');
                }
                if ($data->getStock() === null) {
                    throw new BadRequestHttpException('stock is required for PUT operation');
                }
            }
        } else {
            // For POST, validate required fields
            if ($data->getProduct() === null) {
                throw new BadRequestHttpException('product is required');
            }
            if ($data->getStore() === null) {
                throw new BadRequestHttpException('store is required');
            }
            if ($data->getPrice() === null) {
                throw new BadRequestHttpException('price is required');
            }
            if ($data->getStock() === null) {
                throw new BadRequestHttpException('stock is required');
            }

            // Check for duplicate product/store combination
            $existingCombination = $this->storeProductRepository->findOneBy([
                'product' => $data->getProduct(),
                'store' => $data->getStore()
            ]);

            if ($existingCombination) {
                throw new ConflictHttpException('A store product already exists for this product and store combination');
            }
        }

        // For updates, check if changing product/store would create a duplicate
        if ($isUpdate && isset($existingStoreProduct)) {
            $product = $data->getProduct() ?? $existingStoreProduct->getProduct();
            $store = $data->getStore() ?? $existingStoreProduct->getStore();
            
            if ($product !== $existingStoreProduct->getProduct() || $store !== $existingStoreProduct->getStore()) {
                $existingCombination = $this->storeProductRepository->findOneBy([
                    'product' => $product,
                    'store' => $store
                ]);

                if ($existingCombination && $existingCombination->getId() !== (int) $uriVariables['id']) {
                    throw new ConflictHttpException('A store product already exists for this product and store combination');
                }
            }
        }

        // Validate numeric constraints
        if ($data->getPrice() !== null && $data->getPrice() <= 0) {
            throw new BadRequestHttpException('price must be a positive number');
        }

        if ($data->getStock() !== null && $data->getStock() < 0) {
            throw new BadRequestHttpException('stock must be a non-negative number');
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
