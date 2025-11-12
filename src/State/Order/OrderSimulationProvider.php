<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\OrderInput;
use App\Dto\OrderSimulationOutput;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\PromotionRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderSimulationProvider implements ProcessorInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ValidatorInterface $validator,
        private readonly ProductRepository $productRepository,
        private readonly PromotionRepository $promotionRepository
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): OrderSimulationOutput
    {
        // Check data is OrderInput
        if (!$data instanceof OrderInput) {
            throw new BadRequestHttpException('Invalid input data type');
        }

        // Get the current authenticated user
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            throw new BadRequestHttpException('User not authenticated');
        }

        // Validate input data
        $errors = $this->validator->validate($data);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new BadRequestHttpException('Validation failed: ' . implode(', ', $errorMessages));
        }

        // Validate items array is not empty
        if (empty($data->items)) {
            throw new BadRequestHttpException('Order must contain at least one item');
        }

        // Create output object
        $output = new OrderSimulationOutput();

        // Calculate subtotal from items
        $subtotal = $this->calculateSubtotal($data->items);
        $output->subtotal = $subtotal;
        $output->totalAmount = $subtotal;

        // Process items for output
        $output->items = $this->processItemsForOutput($data->items);

        // Try to apply promotion if provided
        if ($data->promotionCode) {
            $promotionResult = $this->simulatePromotion($data->promotionCode, $subtotal);
            $output->promotionCode = $data->promotionCode;
            $output->promotionValid = $promotionResult['valid'];
            $output->discountAmount = $promotionResult['discount'];
            $output->totalAmount = $subtotal - $promotionResult['discount'];
            $output->promotion = $promotionResult['promotion'];
            $output->promotionError = $promotionResult['error'] ?? null;
        }

        return $output;
    }

    /**
     * Calculate subtotal from order items
     */
    private function calculateSubtotal(array $items): float
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            // Validate item structure
            if (!isset($item->id) || !isset($item->quantity)) {
                throw new BadRequestHttpException('Invalid item structure: id and quantity are required');
            }

            // Validate quantity
            if ($item->quantity <= 0) {
                throw new BadRequestHttpException(sprintf('Invalid quantity for product ID %d: quantity must be greater than 0', $item->id));
            }

            // Find product
            $product = $this->productRepository->find($item->id);
            if (!$product) {
                throw new BadRequestHttpException(sprintf('Product not found with ID: %d', $item->id));
            }

            // Validate product is active
            if (!$product->isActive()) {
                throw new BadRequestHttpException(sprintf('Product %s (ID: %d) is not active', $product->getName(), $product->getId()));
            }

            // Calculate total price
            $productPrice = $product->getTotalPrice() ?? $product->getUnitPrice() ?? 0.0;
            if ($productPrice <= 0) {
                throw new BadRequestHttpException(sprintf('Product %s (ID: %d) has invalid price', $product->getName(), $product->getId()));
            }

            $totalPrice = $productPrice * $item->quantity;
            $subtotal += $totalPrice;
        }

        return $subtotal;
    }

    /**
     * Process items for output
     */
    private function processItemsForOutput(array $items): array
    {
        $outputItems = [];

        foreach ($items as $item) {
            $product = $this->productRepository->find($item->id);
            if (!$product) {
                continue;
            }

            $productPrice = $product->getTotalPrice() ?? $product->getUnitPrice() ?? 0.0;
            $totalPrice = $productPrice * $item->quantity;

            $outputItems[] = [
                'productId' => $product->getId(),
                'productName' => $product->getName(),
                'quantity' => $item->quantity,
                'unitPrice' => $productPrice,
                'totalPrice' => $totalPrice,
            ];
        }

        return $outputItems;
    }

    /**
     * Simulate promotion application without persisting
     */
    private function simulatePromotion(string $promotionCode, float $orderTotal): array
    {
        $promotion = $this->promotionRepository->findValidByCode($promotionCode);

        if (!$promotion) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'promotion' => null,
                'error' => sprintf('Invalid or expired promotion code: %s', $promotionCode),
            ];
        }

        // Check minimum order amount
        if ($promotion->getMinimumOrderAmount() !== null && $orderTotal < $promotion->getMinimumOrderAmount()) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'promotion' => [
                    'id' => $promotion->getId(),
                    'code' => $promotion->getCode(),
                    'name' => $promotion->getName(),
                ],
                'error' => sprintf(
                    'Promotion code %s requires a minimum order amount of %s Ar. Current order total: %s Ar',
                    $promotionCode,
                    number_format($promotion->getMinimumOrderAmount(), 2),
                    number_format($orderTotal, 2)
                ),
            ];
        }

        // Calculate discount
        $discountAmount = $promotion->calculateDiscount($orderTotal);

        if ($discountAmount <= 0) {
            return [
                'valid' => false,
                'discount' => 0.0,
                'promotion' => [
                    'id' => $promotion->getId(),
                    'code' => $promotion->getCode(),
                    'name' => $promotion->getName(),
                ],
                'error' => sprintf('Promotion code %s cannot be applied to this order', $promotionCode),
            ];
        }

        return [
            'valid' => true,
            'discount' => $discountAmount,
            'promotion' => [
                'id' => $promotion->getId(),
                'code' => $promotion->getCode(),
                'name' => $promotion->getName(),
                'description' => $promotion->getDescription(),
                'discountType' => $promotion->getDiscountType()->value,
                'discountValue' => $promotion->getDiscountValue(),
            ],
            'error' => null,
        ];
    }
}

