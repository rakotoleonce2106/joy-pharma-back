<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\Store;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\StoreRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminOrderController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly StoreRepository $storeRepository,
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/api/admin/order/{id}/assign-store', name: 'api_admin_order_assign_store', methods: ['POST'])]
    public function assignStoreAction(int $id, Request $request): JsonResponse
    {
        $jsonContent = $request->getContent();
        
        if (empty($jsonContent)) {
            return $this->json([
                'error' => 'JSON content is required.',
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'Bad Request'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
            
            if (!isset($data['storeId']) || !is_numeric($data['storeId'])) {
                return $this->json([
                    'error' => 'storeId is required and must be a number.',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Bad Request'
                ], Response::HTTP_BAD_REQUEST);
            }

            $order = $this->orderRepository->find($id);
            if (!$order) {
                return $this->json([
                    'error' => 'Order not found.',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Not Found'
                ], Response::HTTP_NOT_FOUND);
            }

            $store = $this->storeRepository->find($data['storeId']);
            if (!$store) {
                return $this->json([
                    'error' => 'Store not found.',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Not Found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Assign store to all order items
            $items = $order->getItems();
            $assignedCount = 0;
            
            foreach ($items as $item) {
                $item->setStore($store);
                $assignedCount++;
            }

            if ($assignedCount === 0) {
                return $this->json([
                    'error' => 'Order has no items to assign store to.',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Bad Request'
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->flush();

            // Notify the store owner when admin assigns a store to the order
            $storeOwner = $store->getOwner();
            if ($storeOwner) {
                $this->notificationService->sendNotification(
                    $storeOwner,
                    'Nouvelle commande assignée',
                    "Une commande {$order->getReference()} a été assignée à votre magasin",
                    'order_new',
                    [
                        'orderId' => $order->getId(),
                        'orderReference' => $order->getReference(),
                        'storeId' => $store->getId(),
                        'storeName' => $store->getName(),
                    ],
                    ['sendPush' => true, 'sendEmail' => false]
                );
            }

            return $this->json([
                'success' => true,
                'message' => "Store assigned successfully to {$assignedCount} item(s).",
                'data' => [
                    'orderId' => $order->getId(),
                    'storeId' => $store->getId(),
                    'storeName' => $store->getName(),
                    'itemsAssigned' => $assignedCount
                ]
            ], Response::HTTP_OK);
        } catch (\JsonException $e) {
            return $this->json([
                'error' => 'Invalid JSON format: ' . $e->getMessage(),
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'Bad Request'
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'An error occurred: ' . $e->getMessage(),
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'Internal Server Error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/admin/order/{id}/assign-deliver', name: 'api_admin_order_assign_deliver', methods: ['POST'])]
    public function assignDeliverAction(int $id, Request $request): JsonResponse
    {
        $jsonContent = $request->getContent();
        
        if (empty($jsonContent)) {
            return $this->json([
                'error' => 'JSON content is required.',
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'Bad Request'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
            
            if (!isset($data['deliverId']) || !is_numeric($data['deliverId'])) {
                return $this->json([
                    'error' => 'deliverId is required and must be a number.',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Bad Request'
                ], Response::HTTP_BAD_REQUEST);
            }

            $order = $this->orderRepository->find($id);
            if (!$order) {
                return $this->json([
                    'error' => 'Order not found.',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Not Found'
                ], Response::HTTP_NOT_FOUND);
            }

            $deliver = $this->userRepository->find($data['deliverId']);
            if (!$deliver) {
                return $this->json([
                    'error' => 'Deliver user not found.',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Not Found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Verify that the user has ROLE_DELIVER
            if (!in_array('ROLE_DELIVER', $deliver->getRoles(), true)) {
                return $this->json([
                    'error' => 'User does not have ROLE_DELIVER.',
                    'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                    'title' => 'Bad Request'
                ], Response::HTTP_BAD_REQUEST);
            }

            $order->setDeliver($deliver);
            $this->entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Deliver assigned successfully.',
                'data' => [
                    'orderId' => $order->getId(),
                    'deliverId' => $deliver->getId(),
                    'deliverName' => $deliver->getFirstName() . ' ' . $deliver->getLastName(),
                    'deliverEmail' => $deliver->getEmail()
                ]
            ], Response::HTTP_OK);
        } catch (\JsonException $e) {
            return $this->json([
                'error' => 'Invalid JSON format: ' . $e->getMessage(),
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'Bad Request'
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'An error occurred: ' . $e->getMessage(),
                'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
                'title' => 'Internal Server Error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

