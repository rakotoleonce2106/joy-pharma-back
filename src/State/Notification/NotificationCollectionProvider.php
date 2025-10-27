<?php

namespace App\State\Notification;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;

class NotificationCollectionProvider implements ProviderInterface
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return [];
        }

        $page = (int) ($context['filters']['page'] ?? 1);
        $limit = min(50, max(1, (int) ($context['filters']['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        return $this->notificationRepository->findByUser($user, $limit, $offset);
    }
}


