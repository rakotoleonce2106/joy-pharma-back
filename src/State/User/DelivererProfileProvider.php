<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DelivererProfileProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?User
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return null;
        }

        if (!in_array('ROLE_DELIVER', $user->getRoles())) {
            throw new AccessDeniedHttpException('Access denied. You must have the deliverer role.');
        }

        return $user;
    }
}
