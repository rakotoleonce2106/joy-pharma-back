<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\PromotionInput;
use App\Entity\Promotion;
use App\Entity\DiscountType;
use App\Repository\PromotionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PromotionProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly PromotionRepository $promotionRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Promotion
    {
        if (!$data instanceof PromotionInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $promotion = $this->promotionRepository->find($uriVariables['id']);
            if (!$promotion) {
                throw new NotFoundHttpException('Promotion not found');
            }
        } else {
            // Check if code already exists
            $existingPromotion = $this->promotionRepository->findByCode($data->code);
            if ($existingPromotion) {
                throw new BadRequestHttpException('Promotion with this code already exists');
            }
            
            $promotion = new Promotion();
        }

        // Check code uniqueness (excluding current promotion)
        if ($isUpdate) {
            $existingPromotion = $this->promotionRepository->findByCode($data->code);
            if ($existingPromotion && $existingPromotion->getId() !== $promotion->getId()) {
                throw new BadRequestHttpException('Promotion with this code already exists');
            }
        }

        // Set properties
        $promotion->setCode($data->code);
        $promotion->setName($data->name);
        $promotion->setDescription($data->description);
        $promotion->setDiscountValue($data->discountValue);
        $promotion->setMinimumOrderAmount($data->minimumOrderAmount);
        $promotion->setMaximumDiscountAmount($data->maximumDiscountAmount);
        $promotion->setStartDate($data->startDate);
        $promotion->setEndDate($data->endDate);
        $promotion->setUsageLimit($data->usageLimit);
        
        if ($data->isActive !== null) {
            $promotion->setIsActive($data->isActive);
        }

        // Set discount type
        try {
            $discountType = DiscountType::from($data->discountType);
            $promotion->setDiscountType($discountType);
        } catch (\ValueError $e) {
            throw new BadRequestHttpException('Invalid discount type: ' . $data->discountType . '. Must be "percentage" or "fixed_amount"');
        }

        // Validate dates if both are provided
        if ($data->startDate && $data->endDate && $data->startDate >= $data->endDate) {
            throw new BadRequestHttpException('Start date must be before end date');
        }

        $this->entityManager->persist($promotion);
        $this->entityManager->flush();

        return $promotion;
    }
}

