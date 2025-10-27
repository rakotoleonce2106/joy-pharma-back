<?php

namespace App\State\Location;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\UpdateLocationInput;
use App\Entity\DeliveryLocation;
use App\Entity\User;
use App\Repository\DeliveryLocationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UpdateLocationProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly DeliveryLocationRepository $locationRepository,
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

        /** @var UpdateLocationInput $input */
        $input = $data;

        // Update user's current location
        $user->setCurrentLatitude((string) $input->latitude);
        $user->setCurrentLongitude((string) $input->longitude);
        $user->setLastLocationUpdate(new \DateTime());

        // Save location history
        $location = new DeliveryLocation();
        $location->setDeliveryPerson($user);
        $location->setLatitude((string) $input->latitude);
        $location->setLongitude((string) $input->longitude);
        $location->setAccuracy($input->accuracy);
        
        if ($input->timestamp) {
            $location->setTimestamp(new \DateTime($input->timestamp));
        }

        $this->locationRepository->save($location, true);

        return [
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => [
                'latitude' => $input->latitude,
                'longitude' => $input->longitude,
                'accuracy' => $input->accuracy,
                'timestamp' => $location->getTimestamp()->format('c')
            ]
        ];
    }
}

