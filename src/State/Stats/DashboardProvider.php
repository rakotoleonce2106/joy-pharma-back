<?php

namespace App\State\Stats;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\DashboardStats;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Service\DateRangeService;
use Symfony\Bundle\SecurityBundle\Security;

class DashboardProvider implements ProviderInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly Security $security,
        private readonly DateRangeService $dateRangeService
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return null;
        }

        $period = $context['filters']['period'] ?? 'today';
        [$startDate, $endDate] = $this->dateRangeService->getDateRange($period);

        $totalDeliveries = $this->orderRepository->countDeliveriesForPerson($user, $startDate, $endDate);
        $totalEarnings = $this->orderRepository->calculateEarningsForPerson($user, $startDate, $endDate);
        $currentOrder = $this->orderRepository->findCurrentOrderForDeliveryPerson($user);

        $delivery = $user->getDelivery();
        $averageRating = $delivery?->getAverageRating();
        $isOnline = $delivery?->getIsOnline() ?? false;
        $lifetimeTotalDeliveries = $delivery?->getTotalDeliveries() ?? 0;
        $lifetimeTotalEarnings = $delivery?->getTotalEarnings() ?? '0.00';

        return new DashboardStats(
            period: $period,
            totalDeliveries: $totalDeliveries,
            totalEarnings: number_format($totalEarnings, 2),
            averageRating: $averageRating,
            isOnline: $isOnline,
            hasActiveOrder: $currentOrder !== null,
            lifetimeStats: [
                'totalDeliveries' => $lifetimeTotalDeliveries,
                'totalEarnings' => $lifetimeTotalEarnings,
                'averageRating' => $averageRating
            ]
        );
    }
}






