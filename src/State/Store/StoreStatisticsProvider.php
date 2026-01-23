<?php

namespace App\State\Store;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\StoreStatistics;
use App\Entity\OrderItemStatus;
use App\Entity\OrderStatus;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Repository\StoreProductRepository;
use App\Repository\StoreRepository;
use App\Service\DateRangeService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreStatisticsProvider implements ProviderInterface
{
    public function __construct(
        private readonly StoreRepository $storeRepository,
        private readonly OrderItemRepository $orderItemRepository,
        private readonly OrderRepository $orderRepository,
        private readonly StoreProductRepository $storeProductRepository,
        private readonly Security $security,
        private readonly DateRangeService $dateRangeService
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Get the authenticated user
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        // Find the store owned by this user
        $store = $this->storeRepository->findOneBy(['owner' => $user]);

        if (!$store) {
            throw new NotFoundHttpException('No store found for this user');
        }

        // Calculate statistics
        $pendingOrdersCount = $this->getPendingOrdersCount($store);
        $todayOrdersCount = $this->getTodayOrdersCount($store);
        $lowStockCount = $this->getLowStockCount($store);
        $todayEarnings = $this->getTodayEarnings($store);
        $weeklyEarnings = $this->getWeeklyEarnings($store);
        $monthlyEarnings = $this->getMonthlyEarnings($store);

        // Get recent orders
        $recentOrders = $this->getRecentOrders($store);

        return new StoreStatistics(
            pendingCount: $pendingOrdersCount,
            recentOrders: $recentOrders,
            recentOrdersCount: count($recentOrders),
            statistics: [
                'pendingOrdersCount' => $pendingOrdersCount,
                'todayOrdersCount' => $todayOrdersCount,
                'lowStockCount' => $lowStockCount,
                'todayEarnings' => $todayEarnings,
                'weeklyEarnings' => $weeklyEarnings,
                'monthlyEarnings' => $monthlyEarnings
            ]
        );
    }


    private function getPendingOrdersCount($store): int
    {
        return $this->orderItemRepository->createQueryBuilder('oi')
            ->select('COUNT(DISTINCT oi.id)')
            ->where('oi.store = :store')
            ->andWhere('oi.storeStatus = :status')
            ->setParameter('store', $store)
            ->setParameter('status', OrderItemStatus::PENDING)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    private function getTodayOrdersCount($store): int
    {
        $today = $this->dateRangeService->getToday();
        
        return $this->orderItemRepository->createQueryBuilder('oi')
            ->select('COUNT(DISTINCT oi.id)')
            ->innerJoin('oi.orderParent', 'o')
            ->where('oi.store = :store')
            ->andWhere('o.createdAt >= :today')
            ->setParameter('store', $store)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    private function getLowStockCount($store, int $threshold = 10): int
    {
        return $this->storeProductRepository->createQueryBuilder('sp')
            ->select('COUNT(sp.id)')
            ->where('sp.store = :store')
            ->andWhere('sp.stock <= :threshold')
            ->andWhere('sp.stock > 0')
            ->andWhere('sp.active = true') // Only active products
            ->setParameter('store', $store)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    private function getTodayEarnings($store): float
    {
        $today = $this->dateRangeService->getToday();
        
        return (float) ($this->orderItemRepository->createQueryBuilder('oi')
            ->select('SUM(oi.storePrice * oi.quantity)')
            ->innerJoin('oi.orderParent', 'o')
            ->where('oi.store = :store')
            ->andWhere('oi.storeStatus IN (:acceptedStatuses)')
            ->andWhere('o.status = :deliveredStatus')
            ->andWhere('o.deliveredAt >= :today')
            ->setParameter('store', $store)
            ->setParameter('acceptedStatuses', [
                OrderItemStatus::ACCEPTED,
                OrderItemStatus::APPROVED
            ])
            ->setParameter('deliveredStatus', OrderStatus::STATUS_DELIVERED)
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }

    private function getWeeklyEarnings($store): float
    {
        $weekStart = $this->dateRangeService->getWeekStart();
        
        return (float) ($this->orderItemRepository->createQueryBuilder('oi')
            ->select('SUM(oi.storePrice * oi.quantity)')
            ->innerJoin('oi.orderParent', 'o')
            ->where('oi.store = :store')
            ->andWhere('oi.storeStatus IN (:acceptedStatuses)')
            ->andWhere('o.status = :deliveredStatus')
            ->andWhere('o.deliveredAt >= :weekStart')
            ->setParameter('store', $store)
            ->setParameter('acceptedStatuses', [
                OrderItemStatus::ACCEPTED,
                OrderItemStatus::APPROVED
            ])
            ->setParameter('deliveredStatus', OrderStatus::STATUS_DELIVERED)
            ->setParameter('weekStart', $weekStart)
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }

    private function getMonthlyEarnings($store): float
    {
        $monthStart = $this->dateRangeService->getMonthStart();
        
        return (float) ($this->orderItemRepository->createQueryBuilder('oi')
            ->select('SUM(oi.storePrice * oi.quantity)')
            ->innerJoin('oi.orderParent', 'o')
            ->where('oi.store = :store')
            ->andWhere('oi.storeStatus IN (:acceptedStatuses)')
            ->andWhere('o.status = :deliveredStatus')
            ->andWhere('o.deliveredAt >= :monthStart')
            ->setParameter('store', $store)
            ->setParameter('acceptedStatuses', [
                OrderItemStatus::ACCEPTED,
                OrderItemStatus::APPROVED
            ])
            ->setParameter('deliveredStatus', OrderStatus::STATUS_DELIVERED)
            ->setParameter('monthStart', $monthStart)
            ->getQuery()
            ->getSingleScalarResult() ?? 0);
    }

    private function getRecentOrders($store, int $limit = 10): array
    {
        $orders = $this->orderRepository->createQueryBuilder('o')
            ->innerJoin('o.orderItems', 'oi')
            ->where('oi.store = :store')
            ->setParameter('store', $store)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($orders as $order) {
            // Count items for this store only
            $itemsCount = 0;
            foreach ($order->getOrderItems() as $item) {
                if ($item->getStore() === $store) {
                    $itemsCount++;
                }
            }

            $result[] = [
                'id' => (string) $order->getId(),
                'reference' => $order->getReference(),
                'status' => $order->getStatus(),
                'totalAmount' => $order->getTotalAmount(),
                'itemsCount' => $itemsCount,
                'scheduledDate' => $order->getScheduledDate()?->format('Y-m-d H:i:s'),
                'location' => $order->getLocation() ? [
                    'address' => $order->getLocation()->getAddress(),
                    'city' => $order->getLocation()->getCity(),
                    'latitude' => $order->getLocation()->getLatitude(),
                    'longitude' => $order->getLocation()->getLongitude(),
                ] : null,
                'owner' => [
                    'id' => $order->getOwner()?->getId(),
                    'email' => $order->getOwner()?->getEmail(),
                    'firstName' => $order->getOwner()?->getFirstName(),
                    'lastName' => $order->getOwner()?->getLastName(),
                ]
            ];
        }

        return $result;
    }
}

