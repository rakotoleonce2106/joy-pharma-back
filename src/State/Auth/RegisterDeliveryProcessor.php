<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterDeliveryProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // $data is the DTO with registration info
        
        // Check if email already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data->email]);

        if ($existingUser) {
            throw new ConflictHttpException('Email already exists');
        }

        // Create new delivery user
        $user = new User();
        $user->setEmail($data->email);
        $user->setFirstName($data->firstName);
        $user->setLastName($data->lastName);
        $user->setPhone($data->phone);
        $user->setVehicleType($data->vehicleType);
        
        if (!empty($data->vehiclePlate)) {
            $user->setVehiclePlate($data->vehiclePlate);
        }

        // Set roles for delivery person (no ROLE_USER as requested)
        $user->setRoles(['ROLE_DELIVER']);

        // Newly created delivery accounts require admin activation
        $user->setActive(false);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPassword($hashedPassword);

        // Attach verification documents if provided
        if (!empty($data->residenceDocument)) {
            $user->setResidenceDocumentFile($data->residenceDocument);
        }
        if (!empty($data->vehicleDocument)) {
            $user->setVehicleDocumentFile($data->vehicleDocument);
        }

        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Generate JWT token
        $token = $this->jwtManager->create($user);

        // Return response with token and user data (like login)
        return [
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'phone' => $user->getPhone(),
                'roles' => $user->getRoles(),
                'userType' => 'delivery',
                'isActive' => $user->isActive(),
                'delivery' => [
                    'vehicleType' => $user->getVehicleType(),
                    'vehiclePlate' => $user->getVehiclePlate(),
                    'isOnline' => $user->isOnline(),
                    'totalDeliveries' => $user->getTotalDeliveries(),
                    'averageRating' => $user->getAverageRating(),
                    'totalEarnings' => $user->getTotalEarnings(),
                ]
            ]
        ];
    }
}

