<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * EventSubscriber for User entity lifecycle events
 * Handles:
 * - Password hashing
 * - Email uniqueness validation
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: User::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
class UserSubscriber
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository
    ) {}

    /**
     * Hash password and validate email uniqueness before persisting (create)
     */
    public function prePersist(User $user, PrePersistEventArgs $event): void
    {
        // Check if email already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $user->getEmail()]);
        if ($existingUser) {
            throw new BadRequestHttpException('User with this email already exists');
        }

        // Hash password if plainPassword is set
        if ($user->getPlainPassword() !== null) {
            $this->userService->hashPassword($user, $user->getPlainPassword());
            $user->eraseCredentials(); // Clear plainPassword after hashing
        } elseif ($user->getPassword() === null) {
            // Generate default password for new users without password
            $this->userService->hashPassword($user);
        }
    }

    /**
     * Hash password before updating
     */
    public function preUpdate(User $user, PreUpdateEventArgs $event): void
    {
        // Hash password if plainPassword is set
        if ($user->getPlainPassword() !== null) {
            $this->userService->hashPassword($user, $user->getPlainPassword());
            $user->eraseCredentials(); // Clear plainPassword after hashing
        }
    }
}

