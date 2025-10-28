<?php

namespace App\State\Stats;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\DashboardStats;
use App\Entity\User;
use App\Repository\OrderRepository;
use Symfony\Bundle\SecurityBundle\Security;

class DashboardProvider implements ProviderInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly Security $security
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
        [$startDate, $endDate] = $this->getDateRange($period);

        $totalDeliveries = $this->orderRepository->countDeliveriesForPerson($user, $startDate, $endDate);
        $totalEarnings = $this->orderRepository->calculateEarningsForPerson($user, $startDate, $endDate);
        $currentOrder = $this->orderRepository->findCurrentOrderForDeliveryPerson($user);

        return new DashboardStats(
            period: $period,
            totalDeliveries: $totalDeliveries,
            totalEarnings: number_format($totalEarnings, 2),
            averageRating: $user->getAverageRating(),
            isOnline: $user->isOnline(),
            hasActiveOrder: $currentOrder !== null,
            lifetimeStats: [
                'totalDeliveries' => $user->getTotalDeliveries(),
                'totalEarnings' => $user->getTotalEarnings(),
                'averageRating' => $user->getAverageRating()
            ]
        );
    }

    private function getDateRange(string $period): array
    {
        $endDate = new \DateTime();
        $endDate->setTime(23, 59, 59);

        return match ($period) {
            'today' => [(new \DateTime())->setTime(0, 0, 0), $endDate],
            'week' => [(new \DateTime())->modify('-7 days')->setTime(0, 0, 0), $endDate],
            'month' => [(new \DateTime())->modify('-30 days')->setTime(0, 0, 0), $endDate],
            'year' => [(new \DateTime())->modify('-365 days')->setTime(0, 0, 0), $endDate],
            default => [(new \DateTime())->setTime(0, 0, 0), $endDate]
        };
    }
}





