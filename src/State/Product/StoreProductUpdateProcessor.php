<?php

namespace App\State\Product;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Product;
use App\Entity\StoreProduct;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\StoreProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StoreProductUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly ValidatorInterface $validator,
        private readonly StoreProductRepository $storeProductRepository,
        private readonly ProductRepository $productRepository
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StoreProduct
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('User not authenticated');
        }

        // Check user is a store owner
        if (!in_array('ROLE_STORE', $user->getRoles(), true)) {
            throw new AccessDeniedHttpException('User must be a store owner');
        }

        if (!$user->getStore()) {
            throw new NotFoundHttpException('User does not have a store');
        }

        // Get the store product ID from URI variables
        $storeProductId = $uriVariables['id'] ?? null;
        if (!$storeProductId) {
            throw new BadRequestHttpException('Store product ID is required in the URL path');
        }

        // Find the existing store product
        $existingStoreProduct = $this->storeProductRepository->find($storeProductId);
        if (!$existingStoreProduct) {
            throw new NotFoundHttpException('Store product not found');
        }

        // Verify the store product belongs to the user's store
        if ($existingStoreProduct->getStore() !== $user->getStore()) {
            throw new AccessDeniedHttpException('You are not authorized to update this store product');
        }

        // Check data is StoreProduct
        if (!$data instanceof StoreProduct) {
            throw new BadRequestHttpException('Invalid input data type');
        }

        // Update fields if provided
        if ($data->getPrice() !== null) {
            if ($data->getPrice() <= 0) {
                throw new BadRequestHttpException('Price must be greater than 0');
            }
            $existingStoreProduct->setPrice($data->getPrice());
        }

        if ($data->getUnitPrice() !== null) {
            $existingStoreProduct->setUnitPrice($data->getUnitPrice());
        }

        if ($data->getStock() !== null) {
            if ($data->getStock() < 0) {
                throw new BadRequestHttpException('Stock must be 0 or greater');
            }
            $existingStoreProduct->setStock($data->getStock());
        }

        // Handle product update if provided
        if ($data->getProduct() !== null) {
            $product = $data->getProduct();
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

            // Check if another store product already exists for this store and product
            $existingStoreProductWithProduct = $this->storeProductRepository->findOneBy([
                'store' => $user->getStore(),
                'product' => $product
            ]);

            // If different store product exists, throw error
            if ($existingStoreProductWithProduct && $existingStoreProductWithProduct !== $existingStoreProduct) {
                throw new BadRequestHttpException('Store product already exists for this product');
            }

            $existingStoreProduct->setProduct($product);
        }

        // Validate the entity
        $violations = $this->validator->validate($existingStoreProduct);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
            }
            throw new BadRequestHttpException('Validation failed: ' . implode(', ', $errors));
        }

        // Persist the changes
        $this->entityManager->persist($existingStoreProduct);
        $this->entityManager->flush();

        return $existingStoreProduct;
    }
}

