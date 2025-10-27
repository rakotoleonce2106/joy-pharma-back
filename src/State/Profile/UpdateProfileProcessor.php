<?php

namespace App\State\Profile;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UpdateProfileProcessor implements ProcessorInterface
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

        $request = $context['request'] ?? null;
        if ($request instanceof Request) {
            $payload = json_decode($request->getContent(), true);

            // Update allowed fields
            if (isset($payload['firstName'])) {
                $user->setFirstName($payload['firstName']);
            }

            if (isset($payload['lastName'])) {
                $user->setLastName($payload['lastName']);
            }

            if (isset($payload['phone'])) {
                $user->setPhone($payload['phone']);
            }

            if (isset($payload['vehicleType'])) {
                $user->setVehicleType($payload['vehicleType']);
            }

            if (isset($payload['vehiclePlate'])) {
                $user->setVehiclePlate($payload['vehiclePlate']);
            }

            $this->em->flush();
        }

        return $user;
    }
}


