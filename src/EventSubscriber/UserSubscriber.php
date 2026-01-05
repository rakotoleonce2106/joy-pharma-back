<?php

namespace App\EventSubscriber;

use App\Entity\Delivery;
use App\Entity\MediaObject;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MediaObjectService;
use App\Service\UserService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * EventSubscriber for User entity lifecycle events
 * Handles:
 * - Password hashing
 * - Email uniqueness validation
 * - Image mapping (user_images)
 * - Delivery creation for ROLE_DELIVER users
 * - Old image cleanup
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: User::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: User::class)]
class UserSubscriber
{
    private ?int $previousImageId = null;

    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository,
        private readonly MediaObjectService $mediaObjectService
    ) {}

    /**
     * Hash password, validate email uniqueness, and set default role before persisting (create)
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

        // Ensure customer has ROLE_USER if no roles are set
        $roles = $user->getRoles();
        if (empty($roles) || (!in_array('ROLE_USER', $roles) && !in_array('ROLE_ADMIN', $roles) && !in_array('ROLE_STORE', $roles) && !in_array('ROLE_DELIVER', $roles))) {
            $roles[] = 'ROLE_USER';
            $user->setRoles($roles);
        }

        // Set user_images mapping for image if set
        if ($user->getImage() instanceof MediaObject && $user->getImage()->getMapping() === null) {
            $user->getImage()->setMapping('user_images');
        }

        // Create Delivery entity if user has ROLE_DELIVER
        if (in_array('ROLE_DELIVER', $user->getRoles()) && $user->getDelivery() === null) {
            $delivery = new Delivery();
            $user->setDelivery($delivery);
        }
    }

    /**
     * Hash password, handle image mapping, and delivery before updating
     */
    public function preUpdate(User $user, PreUpdateEventArgs $event): void
    {
        // Store previous image ID for cleanup
        $this->previousImageId = $user->getImage()?->getId();

        // Hash password if plainPassword is set
        if ($user->getPlainPassword() !== null) {
            $this->userService->hashPassword($user, $user->getPlainPassword());
            $user->eraseCredentials(); // Clear plainPassword after hashing
        }

        // Set user_images mapping for image if set and mapping is not already set
        if ($user->getImage() instanceof MediaObject && $user->getImage()->getMapping() === null) {
            $user->getImage()->setMapping('user_images');
        }

        // Create Delivery entity if user has ROLE_DELIVER and doesn't have one
        if (in_array('ROLE_DELIVER', $user->getRoles()) && $user->getDelivery() === null) {
            $delivery = new Delivery();
            $user->setDelivery($delivery);
        }
    }

    /**
     * Clean up old image after update
     */
    public function postUpdate(User $user, PostUpdateEventArgs $event): void
    {
        // Delete previous MediaObject if it was replaced
        $currentImageId = $user->getImage()?->getId();
        if ($this->previousImageId && $this->previousImageId !== $currentImageId) {
            $this->mediaObjectService->deleteMediaObjectsByIds([$this->previousImageId]);
        }
        
        // Reset for next update
        $this->previousImageId = null;
    }
}

