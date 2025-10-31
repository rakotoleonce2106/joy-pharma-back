<?php

namespace App\State\Store;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\StoreStatistics;
use App\Entity\OrderItemStatus;
use App\Entity\OrderStatus;
use App\Repository\OrderItemRepository;
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

        return new StoreStatistics(
            pendingOrdersCount: $pendingOrdersCount,
            todayOrdersCount: $todayOrdersCount,
            lowStockCount: $lowStockCount,
            todayEarnings: $todayEarnings,
            weeklyEarnings: $weeklyEarnings,
            monthlyEarnings: $monthlyEarnings
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
            ->andWhere('sp.status = 1') // Only active products
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
}

