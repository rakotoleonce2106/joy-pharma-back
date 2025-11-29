<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\ProductPromotionInput;
use App\Entity\ProductPromotion;
use App\Repository\ProductPromotionRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductPromotionProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProductPromotionRepository $productPromotionRepository,
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ProductPromotion
    {
        if (!$data instanceof ProductPromotionInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $productPromotion = $this->productPromotionRepository->find($uriVariables['id']);
            if (!$productPromotion) {
                throw new NotFoundHttpException('Product promotion not found');
            }
        } else {
            $productPromotion = new ProductPromotion();
        }

        // Find product
        $product = $this->productRepository->find($data->productId);
        if (!$product) {
            throw new NotFoundHttpException('Product not found');
        }

        // Set properties
        $productPromotion->setProduct($product);
        $productPromotion->setName($data->name);
        $productPromotion->setDescription($data->description);
        $productPromotion->setDiscountPercentage($data->discountPercentage);
        $productPromotion->setStartDate($data->startDate);
        $productPromotion->setEndDate($data->endDate);
        
        if ($data->isActive !== null) {
            $productPromotion->setIsActive($data->isActive);
        }

        // Validate dates if both are provided
        if ($data->startDate && $data->endDate && $data->startDate >= $data->endDate) {
            throw new BadRequestHttpException('Start date must be before end date');
        }

        $this->entityManager->persist($productPromotion);
        $this->entityManager->flush();

        return $productPromotion;
    }
}

