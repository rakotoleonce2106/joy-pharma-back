<?php

namespace App\State\Store;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Store;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Store
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('User not authenticated');
        }

        if (!in_array('ROLE_STORE', $user->getRoles(), true)) {
            throw new AccessDeniedHttpException('User must have ROLE_STORE');
        }

        $store = $user->getStore();

        if (!$store) {
            throw new NotFoundHttpException('No store associated with this user');
        }

        return $store;
    }
}
