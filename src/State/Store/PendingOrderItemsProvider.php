<?php

namespace App\State\Store;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\OrderItemStatus;
use App\Repository\OrderItemRepository;
use App\Repository\StoreRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PendingOrderItemsProvider implements ProviderInterface
{
    public function __construct(
        private readonly StoreRepository $storeRepository,
        private readonly OrderItemRepository $orderItemRepository,
        private readonly Security $security
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

        // Get pagination parameters
        $page = max(1, (int) ($context['filters']['page'] ?? 1));
        $limit = min(100, max(1, (int) ($context['filters']['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        // Fetch pending order items for this store
        return $this->orderItemRepository->createQueryBuilder('oi')
            ->leftJoin('oi.product', 'p')
            ->addSelect('p')
            ->leftJoin('oi.orderParent', 'o')
            ->addSelect('o')
            ->leftJoin('o.owner', 'customer')
            ->addSelect('customer')
            ->leftJoin('o.location', 'loc')
            ->addSelect('loc')
            ->where('oi.store = :store')
            ->andWhere('oi.storeStatus = :status')
            ->setParameter('store', $store)
            ->setParameter('status', OrderItemStatus::PENDING)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }
}

