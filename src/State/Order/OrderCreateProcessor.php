<?php
// api/src/State/Order/OrderCreateProcessor.php
namespace App\State\Order;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\OrderInput;
use App\Entity\Location;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderItemStatus;
use App\Entity\Payment;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage,
        private ValidatorInterface $validator,
        private RequestStack $requestStack,
        private ProductRepository $productRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Order
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new BadRequestHttpException('No request found');
        }

        // Get the current authenticated user
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            throw new BadRequestHttpException('User not authenticated');
        }

        // Validate input data first
        $errors = $this->validator->validate($data);
        if (count($errors) > 0) {
            throw new BadRequestHttpException((string) $errors);
        }

        // Check data is OrderInput
        if (!$data instanceof OrderInput) {
            throw new BadRequestHttpException('Invalid input data type');
        }

        $order = new Order();
        $order->setOwner($token->getUser());
        $order->setPriority($data->priority);
        $order->setScheduledDate($data->date);
        $order->setPhone($data->phone);
        $order->setNotes($data->notes);

        $totalAmount = 0;

        // Create and persist location only if address data is provided
        if ($data->latitude && $data->longitude && $data->address) {
            $location = new Location();
            $location->setLatitude($data->latitude);
            $location->setLongitude($data->longitude); 
            $location->setAddress($data->address);
            $this->entityManager->persist($location);
            $order->setLocation($location);
        }

        // Process order items
        foreach ($data->items as $item) {
            $product = $this->productRepository->findOneBy(['id' => $item->id]);
            if (!$product) {
                throw new BadRequestHttpException('Product not found');
            }

            $totalPrice = $product->getTotalPrice() * $item->quantity;
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($item->quantity);
            $orderItem->setTotalPrice($totalPrice);
            $orderItem->setOrderParent($order);
            
            // Find and assign store that has this product
            // Get the first store that has this product in stock
            $storeProducts = $product->getStoreProducts();
            if ($storeProducts && !$storeProducts->isEmpty()) {
                $storeProduct = $storeProducts->first();
                if ($storeProduct && $storeProduct->getStore()) {
                    $orderItem->setStore($storeProduct->getStore());
                }
            }
            // Note: Store status will default to PENDING (set in OrderItem constructor)
            
            $this->entityManager->persist($orderItem);
            $order->addItem($orderItem);
            $totalAmount += $totalPrice;
        }

        $order->setTotalAmount($totalAmount);

        // Create and persist payment
        $payment = new Payment();
        $payment->setMethod($data->paymentMethod);
        $payment->setAmount($totalAmount);
        $payment->setReference($order->getReference());
        $this->entityManager->persist($payment);
        $order->setPayment($payment);

        // Persist the main order entity
        $this->entityManager->persist($order);
        
        // Flush all changes to database
        $this->entityManager->flush();

        return $order;
    }
}