<?php

namespace App\Controller\Admin;

use App\Entity\OrderStatus;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly UserRepository $userRepository,
        private readonly ProductRepository $productRepository,
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

        // Get recent orders (last 10)
        $recentOrders = $this->orderRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            10
        );

        // Get available orders for pharmacies (pending orders without delivery person)
        $availableOrders = $this->orderRepository->findAvailableOrders(10);

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

        return $this->render('admin/dashboard.html.twig', [
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'completedOrders' => $completedOrders,
            'totalUsers' => $totalUsers,
            'totalProducts' => $totalProducts,
            'totalRevenue' => $totalRevenue,
            'lastMonthRevenue' => $lastMonthRevenue,
            'recentOrders' => $recentOrders,
            'availableOrders' => $availableOrders,
        ]);
    }
}
