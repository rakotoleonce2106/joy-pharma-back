<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\UserInput;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        if (!$data instanceof UserInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        // Check if this is an update operation
        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $user = $this->userRepository->find($uriVariables['id']);
            if (!$user) {
                throw new NotFoundHttpException('User not found');
            }
        } else {
            // Check if email already exists
            $existingUser = $this->userRepository->findOneBy(['email' => $data->email]);
            if ($existingUser) {
                throw new BadRequestHttpException('User with this email already exists');
            }
            
            $user = new User();
        }

        // Update user properties
        $user->setEmail($data->email);
        $user->setFirstName($data->firstName);
        $user->setLastName($data->lastName);
        $user->setActive($data->active);
        $user->setRoles($data->roles);

        // Handle password
        if ($data->password) {
            $this->userService->hashPassword($user, $data->password);
        } elseif (!$isUpdate) {
            // Generate default password for new users
            $this->userService->hashPassword($user);
        }

        if ($isUpdate) {
            $this->userService->updateUser($user);
        } else {
            $this->userService->createUser($user);
        }

        return $user;
    }
}

