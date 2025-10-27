<?php

namespace App\State\Availability;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\DeliveryScheduleRepository;
use Symfony\Bundle\SecurityBundle\Security;

class ScheduleProvider implements ProviderInterface
{
    public function __construct(
        private readonly DeliveryScheduleRepository $scheduleRepository,
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

        return $this->scheduleRepository->findByDeliveryPerson($user);
    }
}


