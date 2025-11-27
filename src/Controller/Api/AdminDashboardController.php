<?php

namespace App\Controller\Api;

use App\Repository\DeliveryLocationRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use App\Repository\UserRepository;
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

            $data = [
                'counters' => $counters,
                'financials' => $financials,
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
        
        // Count online deliverers based on recent location updates
        $deliverIds = array_map(static fn ($user) => $user->getId(), $delivers);
        $onlineDelivers = 0;
        if (!empty($deliverIds)) {
            $onlineThreshold = new \DateTimeImmutable('-15 minutes');
            $locations = $this->deliveryLocationRepository->findOnlineByUserIdsSince($deliverIds, $onlineThreshold);
            $uniqueUsers = [];
            foreach ($locations as $location) {
                $user = $location->getDeliveryPerson();
                if ($user && !isset($uniqueUsers[$user->getId()])) {
                    $uniqueUsers[$user->getId()] = true;
                }
            }
            $onlineDelivers = count($uniqueUsers);
        }

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
}

