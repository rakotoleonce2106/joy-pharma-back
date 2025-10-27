<?php

namespace App\State\Emergency;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\SOSInput;
use App\Entity\EmergencySOS;
use App\Entity\User;
use App\Repository\EmergencySOSRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SOSProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EmergencySOSRepository $sosRepository,
        private readonly OrderRepository $orderRepository,
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

        /** @var SOSInput $input */
        $input = $data;

        // Create SOS alert
        $sos = new EmergencySOS();
        $sos->setDeliveryPerson($user);
        $sos->setLatitude((string) $input->latitude);
        $sos->setLongitude((string) $input->longitude);
        $sos->setNotes($input->notes);

        // Associate with order if provided
        if ($input->orderId) {
            $order = $this->orderRepository->find($input->orderId);
            if ($order && $order->getDeliver() === $user) {
                $sos->setOrderRef($order);
            }
        }

        $this->sosRepository->save($sos, true);

        return [
            'success' => true,
            'message' => 'Emergency SOS signal sent successfully',
            'data' => [
                'sosId' => $sos->getId(),
                'status' => 'active',
                'location' => [
                    'latitude' => $input->latitude,
                    'longitude' => $input->longitude
                ],
                'emergencyContacts' => [
                    'police' => '17',
                    'ambulance' => '15',
                    'support' => '+212 XXX XXX XXX'
                ]
            ]
        ];
    }
}


