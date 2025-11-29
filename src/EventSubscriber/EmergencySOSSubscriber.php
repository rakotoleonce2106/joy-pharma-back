<?php

namespace App\EventSubscriber;

use App\Entity\EmergencySOS;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

/**
 * EventSubscriber pour envoyer des notifications lors d'alertes SOS
 */
#[AsEntityListener(event: Events::postPersist, method: 'onSOSCreated', entity: EmergencySOS::class)]
readonly class EmergencySOSSubscriber
{
    public function __construct(
        private NotificationService $notificationService,
        private UserRepository $userRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Déclenche une notification lorsqu'une alerte SOS est créée
     */
    public function onSOSCreated(EmergencySOS $sos, PostPersistEventArgs $event): void
    {
        try {
            $deliveryPerson = $sos->getDeliveryPerson();
            if (!$deliveryPerson) {
                return;
            }

            // Trouver tous les administrateurs à notifier
            $admins = $this->userRepository->findBy(['roles' => ['ROLE_ADMIN']]);
            
            foreach ($admins as $admin) {
                $this->notificationService->sendEmergencyNotification(
                    $admin,
                    $deliveryPerson,
                    [
                        'latitude' => $sos->getLatitude(),
                        'longitude' => $sos->getLongitude(),
                    ],
                    ['sendPush' => true, 'sendEmail' => true]
                );
            }

            // Déclencher l'événement n8n pour traitement supplémentaire
            $this->notificationService->getN8nService()->triggerEvent('sos.created', [
                'sosId' => $sos->getId(),
                'deliveryPersonId' => $deliveryPerson->getId(),
                'deliveryPersonName' => $deliveryPerson->getFullName(),
                'latitude' => $sos->getLatitude(),
                'longitude' => $sos->getLongitude(),
                'orderId' => $sos->getOrderRef()?->getId(),
                'notes' => $sos->getNotes(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error sending SOS notification', [
                'sos_id' => $sos->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}

