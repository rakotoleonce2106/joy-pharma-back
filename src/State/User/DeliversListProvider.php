<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\UserRepository;

class DeliversListProvider implements ProviderInterface
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    /**
     * @return array<User>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->userRepository->findByRole('ROLE_DELIVER');
    }
}


