<?php

namespace App\State\Notification;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MarkReadProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
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

        $notificationId = $uriVariables['id'] ?? null;
        if (!$notificationId) {
            throw new NotFoundHttpException('Notification ID not provided');
        }

        $notification = $this->notificationRepository->find($notificationId);

        if (!$notification) {
            throw new NotFoundHttpException('Notification not found');
        }

        if ($notification->getUser() !== $user) {
            throw new AccessDeniedHttpException('You are not authorized to mark this notification as read');
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $this->notificationRepository->save($notification, true);
        }

        return [
            'success' => true,
            'message' => 'Notification marked as read'
        ];
    }
}


