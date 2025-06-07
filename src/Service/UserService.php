<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;


readonly class UserService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private UserRepository        $userRepository
    )
    {
    }

    public function createUser(User $user): void
    {
        $this->manager->persist($user);
        $this->manager->flush();
    }

    public function updateUser(User $user): void
    {
        $this->manager->flush();
    }


    
    public function findAll(): array
    {
        return $this->manager->getRepository(User::class)
            ->findAll();
    }


       public function deleteUser(User $user): void
    {
        $this->manager->remove($user);
        $this->manager->flush();
    }


}
