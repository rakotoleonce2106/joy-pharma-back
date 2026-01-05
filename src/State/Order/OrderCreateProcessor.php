<?php

namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\OrderInput;
use App\Entity\Location;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Payment;
use App\Entity\Promotion;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Repository\ProductPromotionRepository;
use App\Repository\PromotionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ValidatorInterface $validator,
        private readonly ProductRepository $productRepository,
        private readonly PromotionRepository $promotionRepository,
        private readonly ProductPromotionRepository $productPromotionRepository,
        private readonly LoggerInterface $logger
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Order
    {
        if (!$data instanceof Order) {
            throw new BadRequestHttpException('Invalid input data type');
        }

        // Get the current authenticated user
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            throw new BadRequestHttpException('User not authenticated');
        }

        /** @var User $user */
        $user = $token->getUser();

        // Validate input data first
        $errors = $this->validator->validate($data);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            throw new BadRequestHttpException('Validation failed: ' . implode(', ', $errorMessages));
        }

        // Validate items array is not empty
        if ($data->getItems()->isEmpty()) {
            throw new BadRequestHttpException('Order must contain at least one item');
        }

        $this->logger->info('Creating new order', [
            'user_id' => $user->getId(),
            'items_count' => $data->getItems()->count(),
            'payment_method' => $data->getPaymentMethod(),
        ]);

        try {
            // Begin transaction
            $this->entityManager->beginTransaction();

            $order = $data;
            $order->setOwner($user);
            $order->setStatus(OrderStatus::STATUS_PENDING);
            $order->setCreatedAt(new \DateTime());

            // Process order items
            $totalAmount = $this->processOrderItems($order);

            // Apply promotion if provided
            $discountAmount = 0.0;
            if ($order->getPromotionCode()) {
                $discountAmount = $this->applyPromotion($order, $order->getPromotionCode(), $totalAmount);
            }

            $finalAmount = $totalAmount - $discountAmount;
            $order->setTotalAmount($finalAmount);
            $order->setDiscountAmount($discountAmount);

            // Create and persist payment
            $paymentMethod = $order->getPaymentMethod() ?? 'cash';
            $payment = $this->createPayment($paymentMethod, $finalAmount, $order->getReference() ?? 'TMP-'.time());
            $this->entityManager->persist($payment);
            $order->setPayment($payment);

            // Handle location if embedded/IRI
            if ($order->getLocation()) {
                $location = $order->getLocation();
                if (!$location->getId()) {
                    $this->entityManager->persist($location);
                }
                $this->saveLocationToUser($user, $location);
            }

            // Persist the main order entity
            $this->entityManager->persist($order);
            
            // Flush all changes to database
            $this->entityManager->flush();
            
            // After flush, we have the ID, update payment reference if needed
            if ($payment->getReference() === 'TMP-'.time() || str_starts_with($payment->getReference(), 'TMP-')) {
                $payment->setReference($order->getReference());
                $this->entityManager->flush();
            }

            $this->entityManager->commit();

            $this->logger->info('Order created successfully', [
                'order_id' => $order->getId(),
                'order_reference' => $order->getReference(),
                'user_id' => $user->getId(),
                'total_amount' => $finalAmount,
                'discount_amount' => $discountAmount,
                'promotion_code' => $order->getPromotionCode(),
            ]);

            return $order;
        } catch (\Exception $e) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            $this->logger->error('Failed to create order', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new BadRequestHttpException('Failed to create order: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Create location entity from coordinates and address
     */
    private function createLocation(string $latitude, string $longitude, string $address): Location
    {
        $location = new Location();
        $location->setLatitude((float) $latitude);
        $location->setLongitude((float) $longitude);
        $location->setAddress($address);
        
        return $location;
    }

    /**
     * Save location to user's saved locations if it doesn't already exist
     */
    private function saveLocationToUser(User $user, Location $location): void
    {
        // Check if user already has this location (by comparing coordinates and address)
        $existingLocation = null;
        foreach ($user->getLocations() as $userLocation) {
            if (abs($userLocation->getLatitude() - $location->getLatitude()) < 0.0001 &&
                abs($userLocation->getLongitude() - $location->getLongitude()) < 0.0001 &&
                $userLocation->getAddress() === $location->getAddress()) {
                $existingLocation = $userLocation;
                break;
            }
        }

        // If location doesn't exist in user's locations, add it
        if (!$existingLocation) {
            $user->addLocation($location);
        }
    }

    /**
     * Process order items and calculate total amount
     */
    private function processOrderItems(Order $order): float
    {
        $totalAmount = 0.0;

        foreach ($order->getItems() as $orderItem) {
            $product = $orderItem->getProduct();
            if (!$product) {
                throw new BadRequestHttpException('Product is required for each order item');
            }

            // Validate quantity
            if ($orderItem->getQuantity() <= 0) {
                throw new BadRequestHttpException(sprintf('Invalid quantity for product %s: quantity must be greater than 0', $product->getName()));
            }

            // Validate product is active
            if (!$product->isActive()) {
                throw new BadRequestHttpException(sprintf('Product %s (ID: %d) is not active', $product->getName(), $product->getId()));
            }

            // Calculate base product price
            $productPrice = $product->getTotalPrice() ?? $product->getUnitPrice() ?? 0.0;
            if ($productPrice <= 0) {
                throw new BadRequestHttpException(sprintf('Product %s (ID: %d) has invalid price', $product->getName(), $product->getId()));
            }

            // Apply product promotion if exists and not expired
            $productPromotion = $this->productPromotionRepository->findActiveForProduct($product->getId());
            if ($productPromotion && $productPromotion->isValid()) {
                $productPrice = $productPromotion->calculateDiscountedPrice($productPrice);
                $this->logger->info('Product promotion applied', [
                    'product_id' => $product->getId(),
                    'promotion_id' => $productPromotion->getId(),
                    'original_price' => $product->getTotalPrice() ?? $product->getUnitPrice(),
                    'discounted_price' => $productPrice,
                    'discount_percentage' => $productPromotion->getDiscountPercentage(),
                ]);
            }

            $totalPrice = $productPrice * $orderItem->getQuantity();

            $orderItem->setTotalPrice($totalPrice);
            $orderItem->setOrderParent($order);
            
            // Find and assign store that has this product
            $this->assignStoreToOrderItem($orderItem, $product);
            
            $this->entityManager->persist($orderItem);
            $totalAmount += $totalPrice;
        }

        return $totalAmount;
    }

    /**
     * Assign store to order item if product is available in a store
     */
    private function assignStoreToOrderItem(OrderItem $orderItem, $product): void
    {
        $storeProducts = $product->getStoreProducts();
        if ($storeProducts && !$storeProducts->isEmpty()) {
            $storeProduct = $storeProducts->first();
            if ($storeProduct && $storeProduct->getStore()) {
                $orderItem->setStore($storeProduct->getStore());
            }
        }
    }

    /**
     * Apply promotion to order and return discount amount
     */
    private function applyPromotion(Order $order, string $promotionCode, float $orderTotal): float
    {
        $promotion = $this->promotionRepository->findValidByCode($promotionCode);
        
        if (!$promotion) {
            throw new BadRequestHttpException(sprintf('Invalid or expired promotion code: %s', $promotionCode));
        }

        // Check minimum order amount
        if ($promotion->getMinimumOrderAmount() !== null && $orderTotal < $promotion->getMinimumOrderAmount()) {
            throw new BadRequestHttpException(sprintf(
                'Promotion code %s requires a minimum order amount of %s Ar. Current order total: %s Ar',
                $promotionCode,
                number_format($promotion->getMinimumOrderAmount(), 2),
                number_format($orderTotal, 2)
            ));
        }

        // Calculate discount
        $discountAmount = $promotion->calculateDiscount($orderTotal);

        if ($discountAmount <= 0) {
            throw new BadRequestHttpException(sprintf('Promotion code %s cannot be applied to this order', $promotionCode));
        }

        // Associate promotion with order
        $order->setPromotion($promotion);
        
        // Increment usage count
        $promotion->incrementUsageCount();
        $this->entityManager->persist($promotion);

        $this->logger->info('Promotion applied to order', [
            'promotion_code' => $promotionCode,
            'discount_amount' => $discountAmount,
            'order_total' => $orderTotal,
        ]);

        return $discountAmount;
    }

    /**
     * Create payment entity
     */
    private function createPayment(string $paymentMethod, float $amount, string $reference): Payment
    {
        $payment = new Payment();
        $payment->setMethod($paymentMethod);
        $payment->setAmount((string) $amount);
        $payment->setReference($reference);
        
        return $payment;
    }
}