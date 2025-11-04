<?php
// api/src/State/User/UserUpdateProcessor.php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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
            // Handle isOnline - check request content to see if it was provided
            $content = $request->getContent();
            if ($content && $request->getContentTypeFormat() === 'json') {
                $jsonData = json_decode($content, true);
                if (isset($jsonData['isOnline'])) {
                    $user->setIsOnline(filter_var($jsonData['isOnline'], FILTER_VALIDATE_BOOLEAN));
                }
            }
        }

        // Handle multipart form data
        $this->processFormData($user, $request);

        // Validate the user with the update validation groups
        $violations = $this->validator->validate($user, null, ['Default', 'user:update']);
        
        if (count($violations) > 0) {
            throw new BadRequestHttpException('Validation failed: ' . (string) $violations);
        }

        // Persist the changes
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function processFormData(User $user, Request $request): void
    {
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

        if ($request->request->has('isOnline')) {
            $isOnline = filter_var($request->request->get('isOnline'), FILTER_VALIDATE_BOOLEAN);
            $user->setIsOnline($isOnline);
        }

        // Handle file upload for profile image
        if ($request->files->has('imageFile')) {
            $imageFile = $request->files->get('imageFile');
            
            if ($imageFile && $imageFile->isValid()) {
                $user->setImageFile($imageFile);
            }
        }
    }
}