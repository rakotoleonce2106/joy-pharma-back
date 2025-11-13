<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\OrderInput;
use App\Dto\Admin\OrderItemInput;
use App\Entity\Location;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\OrderStatus;
use App\Entity\Priority;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use App\Repository\UserRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly OrderRepository $orderRepository,
        private readonly UserRepository $userRepository,
        private readonly ProductRepository $productRepository,
        private readonly StoreRepository $storeRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Order
    {
        if (!$data instanceof OrderInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $order = $this->orderRepository->find($uriVariables['id']);
            if (!$order) {
                throw new NotFoundHttpException('Order not found');
            }
        } else {
            // Check if reference already exists
            $existingOrder = $this->orderRepository->findOneBy(['reference' => $data->reference]);
            if ($existingOrder) {
                throw new BadRequestHttpException('Order with this reference already exists');
            }
            
            $order = new Order();
        }

        // Set basic properties
        $order->setReference($data->reference);
        $order->setPhone($data->phone);
        $order->setNotes($data->notes);
        $order->setScheduledDate($data->scheduledDate);

        // Set status
        try {
            $status = OrderStatus::from($data->status);
            $order->setStatus($status);
        } catch (\ValueError $e) {
            throw new BadRequestHttpException('Invalid status: ' . $data->status);
        }

        // Set priority
        try {
            $priority = Priority::from($data->priority);
            $order->setPriority($priority);
        } catch (\ValueError $e) {
            throw new BadRequestHttpException('Invalid priority: ' . $data->priority);
        }

        // Set customer
        $customer = $this->userRepository->find($data->customer);
        if (!$customer) {
            throw new BadRequestHttpException('Customer not found');
        }
        $order->setOwner($customer);

        // Set delivery person if provided
        if ($data->deliveryPerson) {
            $deliver = $this->userRepository->find($data->deliveryPerson);
            if (!$deliver) {
                throw new BadRequestHttpException('Delivery person not found');
            }
            $order->setDeliver($deliver);
        }

        // Handle location
        if ($data->latitude && $data->longitude && $data->address) {
            $location = $order->getLocation();
            if (!$location) {
                $location = new Location();
            }
            $location->setLatitude($data->latitude);
            $location->setLongitude($data->longitude);
            $location->setAddress($data->address);
            $order->setLocation($location);
        }

        // Process order items
        $totalAmount = 0.0;
        if (!$isUpdate) {
            $order->getItems()->clear();
        }
        
        foreach ($data->items as $itemInput) {
            if (!$itemInput instanceof OrderItemInput) {
                continue;
            }

            $product = $this->productRepository->find($itemInput->product);
            if (!$product) {
                throw new BadRequestHttpException('Product not found: ' . $itemInput->product);
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($itemInput->quantity);
            $orderItem->setOrderParent($order);

            // Set store if provided
            if ($itemInput->store) {
                $store = $this->storeRepository->find($itemInput->store);
                if ($store) {
                    $orderItem->setStore($store);
                }
            }

            $itemPrice = ($product->getTotalPrice() ?? $product->getUnitPrice() ?? 0) * $itemInput->quantity;
            $orderItem->setTotalPrice($itemPrice);
            $totalAmount += $itemPrice;

            $this->entityManager->persist($orderItem);
            $order->addItem($orderItem);
        }

        $order->setTotalAmount($totalAmount);

        if ($isUpdate) {
            $this->orderService->updateOrder($order);
        } else {
            $this->orderService->createOrder($order);
        }

        return $order;
    }
}

