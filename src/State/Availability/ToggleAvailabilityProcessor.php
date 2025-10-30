<?php

namespace App\State\Availability;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ToggleAvailabilityProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('User not found');
        }

        // Toggle online status
        $user->setIsOnline(!$user->isOnline());
        $this->em->flush();

        return [
            'success' => true,
            'isOnline' => $user->isOnline(),
            'message' => $user->isOnline() ? 'You are now online' : 'You are now offline'
        ];
    }
}






