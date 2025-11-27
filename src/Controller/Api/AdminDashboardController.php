<?php

namespace App\Controller\Api;

use App\Repository\DeliveryLocationRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use App\Repository\UserRepository;
use App\Entity\Order;
use App\Entity\OrderStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly UserRepository $userRepository,
        private readonly ProductRepository $productRepository,
        private readonly StoreRepository $storeRepository,
        private readonly DeliveryLocationRepository $deliveryLocationRepository
    ) {
    }

    #[Route('/api/dashboard', name: 'api_admin_dashboard', methods: ['GET'])]
    public function getDashboard(): JsonResponse
    {
        try {
            $counters = $this->buildCounters();
            $financials = $this->buildFinancials();
            $map = $this->buildMapData();
            $lists = $this->buildLists();

            $data = [
                'counters' => $counters,
                'financials' => $financials,
                'map' => $map,
                'lists' => $lists,
            ];

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    private function buildCounters(): array
    {
        $totalOrders = (int) $this->orderRepository->count([]);
        $pendingOrders = (int) $this->orderRepository->count(['status' => OrderStatus::STATUS_PENDING]);
        $completedOrders = (int) $this->orderRepository->count(['status' => OrderStatus::STATUS_DELIVERED]);
        $totalUsers = (int) $this->userRepository->count([]);
        $totalProducts = (int) $this->productRepository->count([]);
        $totalStores = (int) $this->storeRepository->count([]);

        $delivers = $this->userRepository->findByRole('ROLE_DELIVER');
        $totalDelivers = \count($delivers);
        $onlineDelivers = $this->getOnlineDeliverers($delivers)['count'];

        return [
            'orders' => [
                'total' => $totalOrders,
                'pending' => $pendingOrders,
                'completed' => $completedOrders,
            ],
            'users' => [
                'total' => $totalUsers,
                'deliverers' => [
                    'total' => $totalDelivers,
                    'online' => $onlineDelivers,
                ],
            ],
            'inventory' => [
                'products' => $totalProducts,
                'stores' => $totalStores,
            ],
        ];
    }

    private function buildFinancials(): array
    {
        $totalRevenue = $this->orderRepository->sumDeliveredTotalAmount();

        $currentMonthStart = (new \DateTimeImmutable('first day of this month'))->setTime(0, 0);
        $previousMonthStart = $currentMonthStart->modify('-1 month');
        $lastMonthRevenue = $this->orderRepository->sumDeliveredTotalAmountBetween($previousMonthStart, $currentMonthStart);

        $today = new \DateTimeImmutable('today');
        $todayOrders = $this->orderRepository->countCreatedSince($today);
        $todayRevenue = $this->orderRepository->sumDeliveredTotalAmountSince($today);

        return [
            'totalRevenue' => $totalRevenue,
            'lastMonthRevenue' => $lastMonthRevenue,
            'todayOrders' => $todayOrders,
            'todayRevenue' => $todayRevenue,
        ];
    }

    private function buildMapData(): array
    {
        $storePayload = $this->storeRepository->findAllWithLocations();

        $deliverers = $this->getOnlineDeliverers();

        $activeOrders = $this->orderRepository->findActiveOrdersWithLocation(limit: 50);
        $orderPayload = array_map(
            static function (array $order): array {
                $status = $order['status'] instanceof OrderStatus ? $order['status']->value : $order['status'];
                $location = ($order['latitude'] !== null && $order['longitude'] !== null) ? [
                    'latitude' => $order['latitude'],
                    'longitude' => $order['longitude'],
                    'address' => $order['address'],
                ] : null;

                return [
                    'id' => $order['id'],
                    'reference' => $order['reference'],
                    'totalAmount' => (float) $order['totalAmount'],
                    'status' => $status,
                    'location' => $location,
                ];
            },
            $activeOrders
        );

        return [
            'stores' => $storePayload,
            'deliverers' => [
                'count' => $deliverers['count'],
                'items' => $deliverers['items'],
            ],
            'orders' => $orderPayload,
        ];
    }

    private function buildLists(): array
    {
        $recentOrders = $this->orderRepository->findRecentOrders(10);
        $availableOrders = $this->orderRepository->findAvailableOrders(10);

        return [
            'recentOrders' => array_map([$this, 'normalizeOrder'], $recentOrders),
            'availableOrders' => array_map([$this, 'normalizeOrder'], $availableOrders),
        ];
    }

    private function normalizeOrder(Order $order): array
    {
        $location = $order->getLocation();
        $owner = $order->getOwner();

        return [
            'id' => $order->getId(),
            'reference' => $order->getReference(),
            'status' => $order->getStatus()->value,
            'totalAmount' => (float) $order->getTotalAmount(),
            'createdAt' => $order->getCreatedAt()?->format(\DATE_ATOM),
            'customer' => $owner ? [
                'id' => $owner->getId(),
                'fullName' => $owner->getFullName(),
                'phone' => $order->getPhone(),
            ] : null,
            'location' => $location ? [
                'address' => $location->getAddress(),
                'latitude' => $location->getLatitude(),
                'longitude' => $location->getLongitude(),
            ] : null,
        ];
    }

    private function getOnlineDeliverers(?array $delivers = null): array
    {
        $delivers ??= $this->userRepository->findByRole('ROLE_DELIVER');
        $deliverIds = array_map(static fn ($user) => $user->getId(), $delivers);

        if (empty($deliverIds)) {
            return ['count' => 0, 'items' => []];
        }

        $onlineThreshold = new \DateTimeImmutable('-15 minutes');
        $locations = $this->deliveryLocationRepository->findOnlineByUserIdsSince($deliverIds, $onlineThreshold);

        $unique = [];
        foreach ($locations as $location) {
            $user = $location->getDeliveryPerson();
            if (!$user || isset($unique[$user->getId()])) {
                continue;
            }

            $unique[$user->getId()] = [
                'id' => $user->getId(),
                'fullName' => $user->getFullName(),
                'email' => $user->getEmail(),
                'location' => [
                    'latitude' => $location->getLatitude(),
                    'longitude' => $location->getLongitude(),
                    'address' => $location->getAddress(),
                    'updatedAt' => $location->getUpdatedAt()?->format(\DATE_ATOM),
                ],
            ];
        }

        return [
            'count' => \count($unique),
            'items' => array_values($unique),
        ];
    }
}

