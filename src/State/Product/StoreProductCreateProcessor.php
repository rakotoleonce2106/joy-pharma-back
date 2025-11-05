<?php

namespace App\State\Product;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Product;
use App\Entity\StoreProduct;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StoreProductCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly ValidatorInterface $validator,
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

        // Check data is StoreProduct
        if (!$data instanceof StoreProduct) {
            throw new BadRequestHttpException('Invalid input data type');
        }

        // Validate product is provided
        if (!$data->getProduct()) {
            throw new BadRequestHttpException('Product is required');
        }

        // Get the product entity - API Platform deserializes IRI references automatically
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

        // Check if store product already exists for this store and product
        $existingStoreProduct = $this->entityManager->getRepository(StoreProduct::class)
            ->findOneBy([
                'store' => $user->getStore(),
                'product' => $product
            ]);

        if ($existingStoreProduct) {
            throw new BadRequestHttpException('Store product already exists for this product');
        }

        // Set the store
        $data->setStore($user->getStore());
        $data->setProduct($product);

        // Validate required fields
        if ($data->getPrice() === null || $data->getPrice() <= 0) {
            throw new BadRequestHttpException('Price must be greater than 0');
        }

        if ($data->getStock() === null || $data->getStock() < 0) {
            throw new BadRequestHttpException('Stock must be 0 or greater');
        }

        // Validate the entity
        $violations = $this->validator->validate($data);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
            }
            throw new BadRequestHttpException('Validation failed: ' . implode(', ', $errors));
        }

        // Persist the store product
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}

