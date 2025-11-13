<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\PromotionInput;
use App\Entity\Promotion;
use App\Entity\PromotionType;
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
        $promotion->setValue($data->value);
        $promotion->setStartDate($data->startDate);
        $promotion->setEndDate($data->endDate);
        $promotion->setDescription($data->description);
        $promotion->setMinimumOrderAmount($data->minimumOrderAmount);

        // Set type
        try {
            $type = PromotionType::from($data->type);
            $promotion->setType($type);
        } catch (\ValueError $e) {
            throw new BadRequestHttpException('Invalid promotion type: ' . $data->type);
        }

        // Validate dates
        if ($data->startDate >= $data->endDate) {
            throw new BadRequestHttpException('Start date must be before end date');
        }

        $this->entityManager->persist($promotion);
        $this->entityManager->flush();

        return $promotion;
    }
}

