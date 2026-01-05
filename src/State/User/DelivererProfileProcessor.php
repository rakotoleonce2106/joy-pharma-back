<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DelivererProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var User|null $currentUser */
        $currentUser = $this->security->getUser();

        if (!$currentUser instanceof User || !in_array('ROLE_DELIVER', $currentUser->getRoles())) {
            throw new AccessDeniedHttpException('Access denied. You must be a deliverer to update this profile.');
        }

        // $data is the User entity being updated (deserialized from input)
        if (!$data instanceof User) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // Double check it's the current user's profile
        if ($data->getId() !== $currentUser->getId()) {
            throw new AccessDeniedHttpException('You can only update your own profile.');
        }

        // Handle password update if provided
        if ($data->getPlainPassword()) {
            $hashedPassword = $this->passwordHasher->hashPassword($data, $data->getPlainPassword());
            $data->setPassword($hashedPassword);
            $data->eraseCredentials();
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
