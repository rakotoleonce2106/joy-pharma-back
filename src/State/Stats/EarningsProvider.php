<?php

namespace App\State\Stats;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\EarningsStats;
use App\Entity\OrderStatus;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Service\DateRangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class EarningsProvider implements ProviderInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $em,
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

        $period = $context['filters']['period'] ?? 'week';
        [$startDate, $endDate] = $this->dateRangeService->getDateRange($period);

        $earnings = $this->getDetailedEarnings($user, $startDate, $endDate, $period);
        $totalEarnings = $this->orderRepository->calculateEarningsForPerson($user, $startDate, $endDate);
        $totalDeliveries = $this->orderRepository->countDeliveriesForPerson($user, $startDate, $endDate);

        return new EarningsStats(
            period: $period,
            totalEarnings: number_format($totalEarnings, 2),
            totalDeliveries: $totalDeliveries,
            averagePerDelivery: $totalDeliveries > 0 ? number_format($totalEarnings / $totalDeliveries, 2) : '0.00',
            earnings: $earnings
        );
    }

    private function getDetailedEarnings(User $user, \DateTime $startDate, \DateTime $endDate, string $period): array
    {
        $qb = $this->em->createQueryBuilder();
        
        $groupFormat = $this->dateRangeService->getGroupFormat($period);

        $results = $qb->select(
                "$groupFormat as period",
                'SUM(o.deliveryFee) as earnings',
                'COUNT(o.id) as deliveries'
            )
            ->from('App\Entity\Order', 'o')
            ->where('o.deliver = :user')
            ->andWhere('o.status = :status')
            ->andWhere('o.deliveredAt >= :startDate')
            ->andWhere('o.deliveredAt <= :endDate')
            ->andWhere('o.deliveryFee IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('status', OrderStatus::STATUS_DELIVERED)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('period')
            ->orderBy('period', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(function ($row) {
            return [
                'period' => $row['period'],
                'earnings' => number_format((float)$row['earnings'], 2),
                'deliveries' => (int)$row['deliveries']
            ];
        }, $results);
    }
}






