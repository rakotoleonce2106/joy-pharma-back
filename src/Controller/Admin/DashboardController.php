<?php

namespace App\Controller\Admin;

use App\Entity\OrderStatus;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use App\Repository\StoreRepository;
use App\Repository\DeliveryLocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly UserRepository $userRepository,
        private readonly ProductRepository $productRepository,
        private readonly StoreRepository $storeRepository,
        private readonly DeliveryLocationRepository $deliveryLocationRepository,
    ) {}

    #[Route('/', name:'admin_dashboard')]
    public function index(): Response
    {
        // Get statistics
        $totalOrders = $this->orderRepository->count([]);
        $pendingOrders = $this->orderRepository->count(['status' => OrderStatus::STATUS_PENDING]);
        $completedOrders = $this->orderRepository->count(['status' => OrderStatus::STATUS_DELIVERED]);
        $totalUsers = $this->userRepository->count([]);
        $totalProducts = $this->productRepository->count([]);
        $totalStores = $this->storeRepository->count([]);

        // Get delivers (users with ROLE_DELIVER)
        $allDelivers = $this->userRepository->findByRole('ROLE_DELIVER');
        $totalDelivers = count($allDelivers);
        
        // Get IDs of all delivers
        $deliverIds = array_map(fn($user) => $user->getId(), $allDelivers);
        
        // Get online delivers (those who updated location in last 15 minutes)
        $onlineThreshold = new \DateTime('-15 minutes');
        $onlineDeliveryLocations = [];
        
        if (!empty($deliverIds)) {
            $onlineDeliveryLocations = $this->deliveryLocationRepository->createQueryBuilder('dl')
                ->leftJoin('dl.deliveryPerson', 'u')
                ->addSelect('u')
                ->where('u.id IN (:deliverIds)')
                ->andWhere('dl.updatedAt >= :threshold')
                ->setParameter('deliverIds', $deliverIds)
                ->setParameter('threshold', $onlineThreshold)
                ->getQuery()
                ->getResult();
        }
        
        // Extract unique users from delivery locations
        $onlineDelivers = [];
        $onlineDeliversData = []; // For JSON serialization in map
        $uniqueUserIds = [];
        foreach ($onlineDeliveryLocations as $location) {
            $user = $location->getDeliveryPerson();
            if ($user && !in_array($user->getId(), $uniqueUserIds)) {
                $onlineDelivers[] = [
                    'user' => $user,
                    'location' => $location
                ];
                
                // Prepare data for map JSON
                $onlineDeliversData[] = [
                    'id' => $user->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'fullName' => $user->getFullName(),
                    'email' => $user->getEmail(),
                    'deliveryLocation' => [
                        'latitude' => $location->getLatitude(),
                        'longitude' => $location->getLongitude(),
                        'updatedAt' => [
                            'date' => $location->getUpdatedAt()->format('Y-m-d H:i:s')
                        ]
                    ]
                ];
                
                $uniqueUserIds[] = $user->getId();
            }
        }

        // Get recent orders (last 10)
        $recentOrders = $this->orderRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            10
        );

        // Get available orders for pharmacies (pending orders without delivery person)
        $availableOrders = $this->orderRepository->findAvailableOrders(10);

        // Get all stores with their locations for map
        $stores = $this->storeRepository->createQueryBuilder('s')
            ->leftJoin('s.location', 'l')
            ->addSelect('l')
            ->getQuery()
            ->getResult();

        // Get orders with delivery locations for map
        $ordersWithLocations = $this->orderRepository->createQueryBuilder('o')
            ->leftJoin('o.location', 'l')
            ->leftJoin('o.owner', 'ow')
            ->addSelect('l', 'ow')
            ->where('o.status IN (:statuses)')
            ->setParameter('statuses', [OrderStatus::STATUS_PENDING, OrderStatus::STATUS_CONFIRMED, OrderStatus::STATUS_PROCESSING, OrderStatus::STATUS_SHIPPED])
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        // Calculate total revenue from completed orders
        $totalRevenue = $this->orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.status = :status')
            ->setParameter('status', OrderStatus::STATUS_DELIVERED)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Get revenue from last month for comparison
        $lastMonth = new \DateTime('-1 month');
        $lastMonthRevenue = $this->orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.status = :status')
            ->andWhere('o.deliveredAt >= :lastMonth')
            ->setParameter('status', OrderStatus::STATUS_DELIVERED)
            ->setParameter('lastMonth', $lastMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Today's stats
        $today = new \DateTime('today');
        $todayOrders = $this->orderRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdAt >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $todayRevenue = $this->orderRepository->createQueryBuilder('o')
            ->select('SUM(o.totalAmount)')
            ->where('o.status = :status')
            ->andWhere('o.deliveredAt >= :today')
            ->setParameter('status', OrderStatus::STATUS_DELIVERED)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return $this->render('admin/dashboard.html.twig', [
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'completedOrders' => $completedOrders,
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'totalStores' => $totalStores,
            'totalDelivers' => $totalDelivers,
            'onlineDelivers' => $onlineDelivers,
            'onlineDeliversData' => $onlineDeliversData,
            'onlineDeliversCount' => count($onlineDelivers),
            'totalRevenue' => $totalRevenue,
            'lastMonthRevenue' => $lastMonthRevenue,
            'todayOrders' => $todayOrders,
            'todayRevenue' => $todayRevenue,
            'recentOrders' => $recentOrders,
            'availableOrders' => $availableOrders,
            'stores' => $stores,
            'ordersWithLocations' => $ordersWithLocations,
        ]);
    }
}
