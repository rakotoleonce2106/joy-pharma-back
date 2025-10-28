<?php

namespace App\State\Stats;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\EarningsStats;
use App\Entity\OrderStatus;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class EarningsProvider implements ProviderInterface
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly EntityManagerInterface $em,
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

        $period = $context['filters']['period'] ?? 'week';
        [$startDate, $endDate] = $this->getDateRange($period);

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

    private function getDateRange(string $period): array
    {
        $endDate = new \DateTime();
        $endDate->setTime(23, 59, 59);

        return match ($period) {
            'week' => [(new \DateTime())->modify('-7 days')->setTime(0, 0, 0), $endDate],
            'month' => [(new \DateTime())->modify('-30 days')->setTime(0, 0, 0), $endDate],
            'year' => [(new \DateTime())->modify('-365 days')->setTime(0, 0, 0), $endDate],
            default => [(new \DateTime())->modify('-7 days')->setTime(0, 0, 0), $endDate]
        };
    }

    private function getDetailedEarnings(User $user, \DateTime $startDate, \DateTime $endDate, string $period): array
    {
        $qb = $this->em->createQueryBuilder();
        
        $groupFormat = match ($period) {
            'week', 'month' => "DATE_FORMAT(o.deliveredAt, '%Y-%m-%d')",
            'year' => "DATE_FORMAT(o.deliveredAt, '%Y-%m')",
            default => "DATE_FORMAT(o.deliveredAt, '%Y-%m-%d')"
        };

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





