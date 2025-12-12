<?php

namespace App\Service;

use App\Dto\SocialInput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\File\File;


readonly class UserService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private UserPasswordHasherInterface $passwordHasher,
        private string $projectDir
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


    public function hashPassword(User $user, ?string $password = null): User
    {
        // If no password provided, use default
        $plainPassword = $password ?? 'JoyPharma2025!';
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
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
            $this->setUserImageFromUrl($user, $data->imageUrl);
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
            $this->setUserImageFromUrl($user, $data->imageUrl);
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
        // Persist nécessaire pour que le cascade persist fonctionne avec les nouveaux MediaObject
        $this->manager->persist($user);
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

    private function setUserImageFromUrl(User $user, string $imageUrl): void
    {
        $uploadDir = $this->projectDir . '/public/images/profile';
        
        // Create directory structure if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                throw new \RuntimeException('Failed to create upload directory');
            }
        }

        // Generate unique filename to prevent overwrites
        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $fileName = uniqid('', true) . '.' . $extension;
        $fullPath = $uploadDir . '/' . $fileName;

        // Download and save the image
        $content = @file_get_contents($imageUrl);
        if ($content === false) {
            throw new \RuntimeException('Failed to download image from: ' . $imageUrl);
        }

        if (file_put_contents($fullPath, $content) === false) {
            throw new \RuntimeException('Failed to save image to: ' . $fullPath);
        }

        // Create a File object and set it
        $file = new File($fullPath);
        $user->setImageFile($file);
    }

}
