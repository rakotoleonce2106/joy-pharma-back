<?php
// api/src/State/OrderCreateProcessor.php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage,
        private ValidatorInterface $validator,
        private RequestStack $requestStack
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Order
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            throw new BadRequestHttpException('No request found');
        }

        // Get the current authenticated user
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            throw new BadRequestHttpException('User not authenticated');
        }

        $user = $token->getUser();
        $data->setOwner($user);
        $totalAmount = 0;
        foreach ($data->getItems() as $item) {
            $totalPrice = $item->getProduct()->getPrice() * $item->getQuantity();
            $item->setTotalPrice($totalPrice);
            $totalAmount += $totalPrice;
        }
        $data->setTotalAmount($totalAmount);

        // Persist the changes
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }

    
}