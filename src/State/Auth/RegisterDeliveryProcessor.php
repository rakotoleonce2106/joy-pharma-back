<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Delivery;
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
    ) {}

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

        // Set roles for delivery person (no ROLE_USER as requested)
        $user->setRoles(['ROLE_DELIVER']);

        // Newly created delivery accounts require admin activation
        $user->setActive(false);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPassword($hashedPassword);

        // Create Delivery entity
        $delivery = new Delivery();
        $delivery->setVehicleType($data->vehicleType);

        if (!empty($data->vehiclePlate)) {
            $delivery->setVehiclePlate($data->vehiclePlate);
        }

        // Attach verification documents if provided
        if (!empty($data->residenceDocument)) {
            $delivery->setResidenceDocumentFile($data->residenceDocument);
        }
        if (!empty($data->vehicleDocument)) {
            $delivery->setVehicleDocumentFile($data->vehicleDocument);
        }

        // Link Delivery to User
        $user->setDelivery($delivery);

        // Save user (Delivery will be saved via cascade)
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
                'isActive' => $user->getActive(),
                'delivery' => $delivery ? [
                    'vehicleType' => $delivery->getVehicleType(),
                    'vehiclePlate' => $delivery->getVehiclePlate(),
                    'isOnline' => $delivery->getIsOnline(),
                    'totalDeliveries' => $delivery->getTotalDeliveries(),
                    'averageRating' => $delivery->getAverageRating(),
                    'totalEarnings' => $delivery->getTotalEarnings(),
                ] : null
            ]
        ];
    }
}
