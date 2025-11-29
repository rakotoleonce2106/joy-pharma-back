<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\NotificationType;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer les notifications (push, email, in-app) via n8n
 */
readonly class NotificationService
{
    public function __construct(
        private N8nService $n8nService,
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Crée et envoie une notification complète (in-app + push + email si configuré)
     *
     * @param User $user L'utilisateur destinataire
     * @param string $title Le titre de la notification
     * @param string $message Le message de la notification
     * @param string $type Le type de notification (NotificationType enum)
     * @param array $data Données supplémentaires
     * @param array $options Options d'envoi (sendPush, sendEmail, etc.)
     * @return Notification La notification créée
     */
    public function sendNotification(
        User $user,
        string $title,
        string $message,
        string $type = 'system',
        array $data = [],
        array $options = []
    ): Notification {
        // Créer la notification in-app
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setType(NotificationType::from($type));
        $notification->setData($data);

        $this->notificationRepository->save($notification, true);

        // Envoyer la notification push si l'utilisateur a un token FCM
        $sendPush = $options['sendPush'] ?? true;
        if ($sendPush && $user->getFcmToken()) {
            $this->sendPushNotification($user, $title, $message, $data);
        }

        // Envoyer l'email si demandé
        $sendEmail = $options['sendEmail'] ?? false;
        if ($sendEmail && $user->getEmail()) {
            $this->sendEmailNotification($user, $title, $message, $data);
        }

        return $notification;
    }

    /**
     * Envoie une notification push à un utilisateur
     *
     * @param User $user L'utilisateur destinataire
     * @param string $title Le titre
     * @param string $body Le corps
     * @param array $data Données supplémentaires
     * @return bool
     */
    public function sendPushNotification(User $user, string $title, string $body, array $data = []): bool
    {
        if (!$user->getFcmToken()) {
            $this->logger->warning('Cannot send push notification: user has no FCM token', [
                'user_id' => $user->getId()
            ]);
            return false;
        }

        return $this->n8nService->sendPushNotification(
            $user->getFcmToken(),
            $title,
            $body,
            $data
        );
    }

    /**
     * Envoie une notification email à un utilisateur
     *
     * @param User $user L'utilisateur destinataire
     * @param string $subject Le sujet
     * @param string $htmlBody Le corps HTML
     * @param array $data Données supplémentaires pour le template
     * @return bool
     */
    public function sendEmailNotification(User $user, string $subject, string $htmlBody, array $data = []): bool
    {
        if (!$user->getEmail()) {
            $this->logger->warning('Cannot send email notification: user has no email', [
                'user_id' => $user->getId()
            ]);
            return false;
        }

        return $this->n8nService->sendEmail(
            $user->getEmail(),
            $subject,
            $htmlBody
        );
    }

    /**
     * Envoie une notification pour un changement de statut de commande
     *
     * @param User $user L'utilisateur
     * @param string $orderReference La référence de la commande
     * @param string $status Le nouveau statut
     * @param array $options Options d'envoi
     * @return Notification
     */
    public function sendOrderStatusNotification(
        User $user,
        string $orderReference,
        string $status,
        array $options = []
    ): Notification {
        $statusLabels = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'processing' => 'En traitement',
            'shipped' => 'Expédiée',
            'collected' => 'Récupérée',
            'delivered' => 'Livrée',
            'cancelled' => 'Annulée',
        ];

        $statusLabel = $statusLabels[$status] ?? $status;

        return $this->sendNotification(
            $user,
            'Statut de votre commande',
            "Votre commande {$orderReference} est maintenant : {$statusLabel}",
            'order_status',
            [
                'orderReference' => $orderReference,
                'status' => $status,
            ],
            $options
        );
    }

    /**
     * Envoie une notification pour une nouvelle commande
     *
     * @param User $storeOwner Le propriétaire du magasin
     * @param string $orderReference La référence de la commande
     * @param float $totalAmount Le montant total
     * @param array $options Options d'envoi
     * @return Notification
     */
    public function sendNewOrderNotification(
        User $storeOwner,
        string $orderReference,
        float $totalAmount,
        array $options = []
    ): Notification {
        return $this->sendNotification(
            $storeOwner,
            'Nouvelle commande',
            "Vous avez reçu une nouvelle commande {$orderReference} d'un montant de {$totalAmount} Ar",
            'order_new',
            [
                'orderReference' => $orderReference,
                'totalAmount' => $totalAmount,
            ],
            $options
        );
    }

    /**
     * Envoie une notification d'urgence (SOS)
     *
     * @param User $admin L'administrateur à notifier
     * @param User $deliveryPerson Le livreur en détresse
     * @param array $location Les coordonnées GPS
     * @param array $options Options d'envoi
     * @return Notification
     */
    public function sendEmergencyNotification(
        User $admin,
        User $deliveryPerson,
        array $location,
        array $options = []
    ): Notification {
        return $this->sendNotification(
            $admin,
            'Alerte SOS - Livreur en détresse',
            "Le livreur {$deliveryPerson->getFullName()} a déclenché une alerte SOS",
            'emergency',
            [
                'deliveryPersonId' => $deliveryPerson->getId(),
                'deliveryPersonName' => $deliveryPerson->getFullName(),
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
            ],
            array_merge(['sendEmail' => true, 'sendPush' => true], $options)
        );
    }

    /**
     * Envoie une notification de promotion
     *
     * @param User $user L'utilisateur
     * @param string $promotionTitle Le titre de la promotion
     * @param string $promotionDescription La description
     * @param array $options Options d'envoi
     * @return Notification
     */
    public function sendPromotionNotification(
        User $user,
        string $promotionTitle,
        string $promotionDescription,
        array $options = []
    ): Notification {
        return $this->sendNotification(
            $user,
            $promotionTitle,
            $promotionDescription,
            'promotion',
            [
                'promotionTitle' => $promotionTitle,
            ],
            $options
        );
    }

    /**
     * Récupère le service n8n (pour les EventSubscribers)
     *
     * @return N8nService
     */
    public function getN8nService(): N8nService
    {
        return $this->n8nService;
    }
}

