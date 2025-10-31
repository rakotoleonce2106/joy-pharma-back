<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\BusinessHours;
use App\Entity\ContactInfo;
use App\Entity\Location;
use App\Entity\Store;
use App\Entity\StoreSetting;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterStoreProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Check if email already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data->email]);

        if ($existingUser) {
            throw new ConflictHttpException('Email already exists');
        }

        // Create store
        $store = new Store();
        $store->setName($data->storeName);
        
        if (!empty($data->storeDescription)) {
            $store->setDescription($data->storeDescription);
        }

        // Create contact info for store
        $contact = new ContactInfo();
        $contact->setPhone($data->storePhone ?? $data->phone);
        $contact->setEmail($data->storeEmail ?? $data->email);
        $store->setContact($contact);

        // Create location for store
        $location = new Location();
        $location->setAddress($data->storeAddress);
        
        // Optional location fields
        if (!empty($data->storeCity)) {
            $location->setCity($data->storeCity);
        }
        if (!empty($data->storeLatitude) && !empty($data->storeLongitude)) {
            $location->setLatitude($data->storeLatitude);
            $location->setLongitude($data->storeLongitude);
        }
        
        $store->setLocation($location);

        // Initialize StoreSetting with default business hours
        $storeSetting = new StoreSetting();
        $store->setSetting($storeSetting);

        // Create store owner user
        $user = new User();
        $user->setEmail($data->email);
        $user->setFirstName($data->firstName);
        $user->setLastName($data->lastName);
        $user->setPhone($data->phone);
        $user->setRoles(['ROLE_USER', 'ROLE_STORE']);
        $user->setStore($store);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPassword($hashedPassword);

        // Save entities - persist StoreSetting and its BusinessHours
        $this->entityManager->persist($contact);
        $this->entityManager->persist($location);
        $this->entityManager->persist($store);
        $this->entityManager->persist($storeSetting);
        $this->entityManager->persist($user);
        
        // Persist BusinessHours if they don't have IDs (they're created in StoreSetting constructor)
        $this->persistBusinessHoursIfNeeded($storeSetting);
        
        $this->entityManager->flush();

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        // Return response with token and user data
        return [
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'roles' => $user->getRoles(),
                'userType' => 'store',
                'isActive' => $user->isActive(),
                'store' => [
                    'id' => $store->getId(),
                    'name' => $store->getName(),
                    'description' => $store->getDescription(),
                    'address' => $location->getAddress(),
                    'city' => $location->getCity(),
                    'phone' => $contact->getPhone(),
                    'email' => $contact->getEmail(),
                ]
            ]
        ];
    }

    private function persistBusinessHoursIfNeeded(StoreSetting $setting): void
    {
        // Persist any BusinessHours that haven't been persisted yet
        // (created in StoreSetting constructor)
        $methods = [
            'getMondayHours',
            'getTuesdayHours',
            'getWednesdayHours',
            'getThursdayHours',
            'getFridayHours',
            'getSaturdayHours',
            'getSundayHours'
        ];

        foreach ($methods as $method) {
            $hours = $setting->$method();
            if ($hours && $hours->getId() === null) {
                $this->entityManager->persist($hours);
            }
        }
    }
}

