<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;


readonly class OrderService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private OrderRepository        $orderRepository
    )
    {
    }

    public function createOrder(Order $order): void
    {
        $this->manager->persist($order);
        $this->manager->flush();
    }



    public function updateOrder(Order $order): void
    {
        $this->manager->flush();
    }


    public function findAll(): array
    {
        return $this->manager->getRepository(Order::class)
            ->findAll();
    }

    public function batchDeleteOrder(array $orderIds): void
    {


        foreach ($orderIds as $id) {
            $order= $this->orderRepository->find($id);
            if ($order) {
                $this->deleteOrder($order);

            }
        }


    }

    public function deleteOrder(Order $order): void
    {
        $this->manager->remove($order);
        $this->manager->flush();
    }
}
