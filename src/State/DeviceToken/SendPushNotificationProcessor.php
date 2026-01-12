<?php

namespace App\State\DeviceToken;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\SendPushNotificationInput;
use App\Entity\NotificationType;
use App\Repository\UserRepository;
use App\Service\FirebasePushService;
use App\Service\NotificationService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Processor for admin to send push notifications.
 */
readonly class SendPushNotificationProcessor implements ProcessorInterface
{
    public function __construct(
        private FirebasePushService $firebasePushService,
        private NotificationService $notificationService,
        private UserRepository $userRepository
    ) {
    }

    /**
     * @param SendPushNotificationInput $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        switch ($data->type) {
            case 'single_user':
                return $this->sendToSingleUser($data);

            case 'multiple_users':
                return $this->sendToMultipleUsers($data);

            case 'topic':
                return $this->sendToTopic($data);

            case 'broadcast':
                return $this->sendBroadcast($data);

            default:
                throw new BadRequestHttpException('Invalid notification type');
        }
    }

    private function sendToSingleUser(SendPushNotificationInput $data): array
    {
        if (!$data->userId) {
            throw new BadRequestHttpException('userId is required for single_user type');
        }

        $user = $this->userRepository->find($data->userId);

        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        // Send push notification
        $result = $this->firebasePushService->sendToUser(
            $user,
            $data->title,
            $data->body,
            $data->data
        );

        // Create in-app notification if requested
        if ($data->createInAppNotification) {
            $this->notificationService->sendNotification(
                $user,
                $data->title,
                $data->body,
                'system',
                $data->data,
                ['sendPush' => false, 'sendEmail' => false] // Already sent push
            );
        }

        return [
            'success' => $result['success'],
            'type' => 'single_user',
            'userId' => $data->userId,
            'push_result' => $result,
            'in_app_created' => $data->createInAppNotification,
        ];
    }

    private function sendToMultipleUsers(SendPushNotificationInput $data): array
    {
        if (empty($data->userIds)) {
            throw new BadRequestHttpException('userIds is required for multiple_users type');
        }

        $users = $this->userRepository->findBy(['id' => $data->userIds]);

        if (empty($users)) {
            throw new NotFoundHttpException('No users found');
        }

        // Send push notification
        $result = $this->firebasePushService->sendToUsers(
            $users,
            $data->title,
            $data->body,
            $data->data
        );

        // Create in-app notifications if requested
        if ($data->createInAppNotification) {
            foreach ($users as $user) {
                $this->notificationService->sendNotification(
                    $user,
                    $data->title,
                    $data->body,
                    'system',
                    $data->data,
                    ['sendPush' => false, 'sendEmail' => false]
                );
            }
        }

        return [
            'success' => $result['success'],
            'type' => 'multiple_users',
            'users_count' => count($users),
            'push_result' => $result,
            'in_app_created' => $data->createInAppNotification,
        ];
    }

    private function sendToTopic(SendPushNotificationInput $data): array
    {
        if (!$data->topic) {
            throw new BadRequestHttpException('topic is required for topic type');
        }

        $success = $this->firebasePushService->sendToTopic(
            $data->topic,
            $data->title,
            $data->body,
            $data->data
        );

        return [
            'success' => $success,
            'type' => 'topic',
            'topic' => $data->topic,
        ];
    }

    private function sendBroadcast(SendPushNotificationInput $data): array
    {
        $result = $this->firebasePushService->sendBroadcast(
            $data->title,
            $data->body,
            $data->data
        );

        // For broadcast, we can optionally create in-app notifications for all users
        // This is typically not done due to performance, but can be enabled

        return [
            'success' => $result['success'] ?? false,
            'type' => 'broadcast',
            'push_result' => $result,
        ];
    }
}
