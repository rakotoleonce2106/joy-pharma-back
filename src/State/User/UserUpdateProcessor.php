<?php
// api/src/State/User/UserUpdateProcessor.php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Delivery;
use App\Entity\MediaObject;
use App\Entity\User;
use App\Exception\ValidationFailedException;
use App\Service\MediaObjectService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage,
        private ValidatorInterface $validator,
        private MediaObjectService $mediaObjectService
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        // Get the current authenticated user
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            throw new BadRequestHttpException('User not authenticated');
        }

        $user = $token->getUser();

        // Handle JSON-LD data - API Platform automatically deserializes IRI to MediaObject
        if ($data instanceof User) {
            // Data is already denormalized by API Platform
            if ($data->getFirstName() !== null) {
                $user->setFirstName($data->getFirstName());
            }
            if ($data->getLastName() !== null) {
                $user->setLastName($data->getLastName());
            }
            if ($data->getPhone() !== null) {
                $user->setPhone($data->getPhone());
            }

            // Handle image: API Platform automatically deserializes IRI to MediaObject entity (JSON-LD)
            if ($data->getImage() instanceof MediaObject) {
                $previousImageId = $user->getImage()?->getId();
                // Use user_images mapping for user avatars
                $data->getImage()->setMapping('user_images');
                $user->setImage($data->getImage());
                
                // Flush MediaObject so VichUploader can process if needed
                $this->entityManager->flush();
                
                // Delete previous MediaObject if it was replaced
                if ($previousImageId && $previousImageId !== $user->getImage()?->getId()) {
                    $this->mediaObjectService->deleteMediaObjectsByIds([$previousImageId]);
                }
            }

            // Handle delivery updates if user has ROLE_DELIVER
            if (in_array('ROLE_DELIVER', $user->getRoles())) {
                $delivery = $user->getDelivery();
                if (!$delivery) {
                    $delivery = new Delivery();
                    $user->setDelivery($delivery);
                }
            }
        }

        // Validate the user with the update validation groups
        $violations = $this->validator->validate($user, null, ['Default', 'user:update']);
        
        if (count($violations) > 0) {
            throw new ValidationFailedException($violations);
        }

        // Persist the changes
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}