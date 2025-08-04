<?php

namespace App\Service;

use App\Dto\SocialInput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


readonly class UserService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private UserPasswordHasherInterface $passwordHasher,
        private MediaFileService $fileService
    )
    {
    }

    public function createUser(User $user): void
    {
        $this->manager->persist($user);
        $this->manager->flush();
    }

    public function persistUser(User $user){
        $this->manager->persist($user);
    }


    public function hashPassword(User $user, string $password = null): User
    {
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password ?? $user->getPlainPassword() ?? 'JoyPharma2025');
        $user->setPassword($hashedPassword);
        return $user;
    }

    public function generatePassword(): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 8; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function saveUser(User $user): void
    {
        // Persist to the database
        $this->manager->persist($user);
        $this->manager->flush();

    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->manager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

        public function createUserByGoogle(SocialInput $data): User
    {
        $user = new User();
        $user->setEmail($data->email);
        $user->setFirstName($data->firstName);
        $user->setLastName($data->lastName);
        $user->setGoogleId($data->socialId);
        if ($data->imageUrl) {
            $mediaFile = $this->fileService->createMediaFileByUrl($data->imageUrl, "profils");
            $user->setImage($mediaFile);
        }
        $user->setRoles(['ROLE_USER']);
        return $user;
    }

    public function createUserByFacebook(SocialInput $data): User
    {
        $user = new User();
        $user->setEmail($data->email);
        $user->setFirstName($data->firstName);
        $user->setLastName($data->lastName);
        $user->setFacebookId($data->socialId);
        if ($data->imageUrl) {
            $mediaFile = $this->fileService->createMediaFileByUrl($data->imageUrl, "profils");
            $user->setImage($mediaFile);
        }
        $user->setRoles(['ROLE_USER']);
        return $user;
    }



    public function getUserByFacebokId(string $facebookId): ?User
    {
        return $this->manager->getRepository(User::class)->findOneBy(['facebookId' => $facebookId]);
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
