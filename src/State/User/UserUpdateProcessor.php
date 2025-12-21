<?php
// api/src/State/User/UserUpdateProcessor.php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Delivery;
use App\Entity\MediaObject;
use App\Entity\User;
use App\Exception\ValidationFailedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorage,
        private ValidatorInterface $validator,
        private RequestStack $requestStack
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            throw new BadRequestHttpException('No request found');
        }

        // Get the current authenticated user
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser() instanceof User) {
            throw new BadRequestHttpException('User not authenticated');
        }

        $user = $token->getUser();

        // Handle JSON body data if available
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
            // Handle delivery updates if user has ROLE_DELIVER
            if (in_array('ROLE_DELIVER', $user->getRoles())) {
                $delivery = $user->getDelivery();
                if (!$delivery) {
                    // Create delivery if it doesn't exist
                    $delivery = new Delivery();
                    $user->setDelivery($delivery);
                }

            // Handle isOnline - check request content to see if it was provided
            $content = $request->getContent();
            if ($content && $request->getContentTypeFormat() === 'json') {
                $jsonData = json_decode($content, true);
                if (isset($jsonData['isOnline'])) {
                        $delivery->setIsOnline(filter_var($jsonData['isOnline'], FILTER_VALIDATE_BOOLEAN));
                    }
                }
            }
        }

        // Handle multipart form data
        $needsFlush = $this->processFormData($user, $request);

        // Flush MediaObjects if any were created/updated so VichUploader can process the uploads
        if ($needsFlush) {
            $this->entityManager->flush();
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

    private function processFormData(User $user, Request $request): bool
    {
        $needsFlush = false;
        
        // Handle text fields
        if ($request->request->has('firstName')) {
            $user->setFirstName($request->request->get('firstName'));
        }

        if ($request->request->has('lastName')) {
            $user->setLastName($request->request->get('lastName'));
        }

        if ($request->request->has('phone')) {
            $user->setPhone($request->request->get('phone'));
        }

        // Handle delivery updates if user has ROLE_DELIVER
        if (in_array('ROLE_DELIVER', $user->getRoles())) {
            $delivery = $user->getDelivery();
            if (!$delivery) {
                // Create delivery if it doesn't exist
                $delivery = new Delivery();
                $user->setDelivery($delivery);
        }

        if ($request->request->has('isOnline')) {
            $isOnline = filter_var($request->request->get('isOnline'), FILTER_VALIDATE_BOOLEAN);
                $delivery->setIsOnline($isOnline);
            }

            if ($request->request->has('vehicleType')) {
                $delivery->setVehicleType($request->request->get('vehicleType'));
            }

            if ($request->request->has('vehiclePlate')) {
                $delivery->setVehiclePlate($request->request->get('vehiclePlate'));
            }
        }

        // Handle file upload for profile image
        // Try multiple possible field names for compatibility
        $imageFile = null;
        if ($request->files->has('imageFile')) {
            $imageFile = $request->files->get('imageFile');
        } elseif ($request->files->has('image')) {
            $imageFile = $request->files->get('image');
        }

        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            if ($user->getImage()) {
                // Update existing image
                $existingImage = $user->getImage();
                $existingImage->setFile($imageFile);
                $existingImage->setMapping('media_object');
                // Ensure MediaObject is managed
                if (!$this->entityManager->contains($existingImage)) {
                    $this->entityManager->persist($existingImage);
                }
                $needsFlush = true;
            } else {
                // Create new MediaObject for image
                $imageMediaObject = new MediaObject();
                $imageMediaObject->setFile($imageFile);
                $imageMediaObject->setMapping('media_object');
                $this->entityManager->persist($imageMediaObject);
                $user->setImage($imageMediaObject);
                $needsFlush = true;
            }
        }

        return $needsFlush;
    }
}