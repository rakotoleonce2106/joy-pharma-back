<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActivateDeliverProcessor implements ProcessorInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->em->getRepository(User::class)->find($uriVariables['id'] ?? 0);
        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        // Ensure it's a deliver
        if (!in_array('ROLE_DELIVER', $user->getRoles(), true)) {
            throw new NotFoundHttpException('Not a deliver');
        }

        $payload = $context['request']->toArray() ?? [];
        $isActive = (bool)($payload['isActive'] ?? true);

        $user->setActive($isActive);
        $this->em->flush();

        return [
            'id' => $user->getId(),
            'isActive' => $user->getActive(),
        ];
    }
}


