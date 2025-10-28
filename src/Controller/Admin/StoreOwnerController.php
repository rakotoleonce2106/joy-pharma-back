<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use App\Repository\StoreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STORE')]
class StoreOwnerController extends AbstractController
{
    public function __construct(
        private readonly StoreRepository $storeRepository,
        private readonly OrderItemRepository $orderItemRepository,
        private readonly OrderRepository $orderRepository,
    ) {}

    #[Route('/store/dashboard', name: 'store_dashboard')]
    public function dashboard(Request $request): Response
    {
        $user = $this->getUser();
        
        // Get the store owned by this user
        $store = $this->storeRepository->findOneBy(['owner' => $user]);
        
        if (!$store) {
            throw $this->createNotFoundException('Store not found for this user');
        }

        // Get pending order items for this store
        $pendingOrderItems = $this->orderItemRepository->createQueryBuilder('oi')
            ->leftJoin('oi.orderParent', 'o')
            ->leftJoin('oi.product', 'p')
            ->leftJoin('oi.store', 's')
            ->addSelect('o', 'p', 's')
            ->where('oi.store = :store')
            ->andWhere('oi.storeStatus = :pending')
            ->setParameter('store', $store)
            ->setParameter('pending', \App\Entity\OrderItemStatus::PENDING)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Get store settings with business hours
        $storeSetting = $store->getSetting();

        return $this->render('admin/store-owner/dashboard.html.twig', [
            'store' => $store,
            'storeSetting' => $storeSetting,
            'pendingOrderItems' => $pendingOrderItems,
        ]);
    }

    #[Route('/store/orders', name: 'store_orders')]
    public function orders(Request $request): Response
    {
        $user = $this->getUser();
        $store = $this->storeRepository->findOneBy(['owner' => $user]);
        
        if (!$store) {
            throw $this->createNotFoundException('Store not found for this user');
        }

        // Get all order items for this store
        $orderItems = $this->orderItemRepository->createQueryBuilder('oi')
            ->leftJoin('oi.orderParent', 'o')
            ->leftJoin('oi.product', 'p')
            ->leftJoin('o.owner', 'customer')
            ->addSelect('o', 'p', 'customer')
            ->where('oi.store = :store')
            ->setParameter('store', $store)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/store-owner/orders.html.twig', [
            'store' => $store,
            'orderItems' => $orderItems,
        ]);
    }
}

