<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Delivery;
use App\Entity\MediaObject;
use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterDeliveryProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private AuthenticationSuccessHandler $authenticationSuccessHandler,
        private IriConverterInterface $iriConverter,
        private EmailVerificationService $emailVerificationService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        try {
            // $data is the DTO with registration info
            
            // Check if data is properly formed
            if (!is_object($data)) {
                throw new BadRequestHttpException('Invalid request data');
            }

            // Validate required fields with proper checks
            if (!property_exists($data, 'email') || empty(trim($data->email ?? ''))) {
                throw new BadRequestHttpException('Email is required');
            }

            if (!property_exists($data, 'firstName') || empty(trim($data->firstName ?? ''))) {
                throw new BadRequestHttpException('First name is required');
            }

            if (!property_exists($data, 'lastName') || empty(trim($data->lastName ?? ''))) {
                throw new BadRequestHttpException('Last name is required');
            }

            if (!property_exists($data, 'phone') || empty(trim($data->phone ?? ''))) {
                throw new BadRequestHttpException('Phone is required');
            }

            if (!property_exists($data, 'password') || empty($data->password ?? '')) {
                throw new BadRequestHttpException('Password is required');
            }

            if (!property_exists($data, 'vehicleType') || empty(trim($data->vehicleType ?? ''))) {
                throw new BadRequestHttpException('Vehicle type is required');
            }

            // Validate required documents
            if (!property_exists($data, 'residenceDocument') || empty($data->residenceDocument)) {
                throw new BadRequestHttpException('Residence document IRI is required');
            }

            if (!property_exists($data, 'vehicleDocument') || empty($data->vehicleDocument)) {
                throw new BadRequestHttpException('Vehicle document IRI is required');
            }
            
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

            // Attach verification documents (required)
            try {
                // Resolve IRIs to MediaObject entities
                try {
                    $residenceMediaObject = $this->iriConverter->getResourceFromIri($data->residenceDocument);
                } catch (\Exception $e) {
                    throw new BadRequestHttpException('Invalid residence document IRI', $e);
                }

                try {
                    $vehicleMediaObject = $this->iriConverter->getResourceFromIri($data->vehicleDocument);
                } catch (\Exception $e) {
                    throw new BadRequestHttpException('Invalid vehicle document IRI', $e);
                }

                if (!$residenceMediaObject instanceof MediaObject) {
                    throw new BadRequestHttpException('Residence document IRI must point to a MediaObject');
                }

                if (!$vehicleMediaObject instanceof MediaObject) {
                    throw new BadRequestHttpException('Vehicle document IRI must point to a MediaObject');
                }

                $delivery->setResidenceDocument($residenceMediaObject);
                $delivery->setVehicleDocument($vehicleMediaObject);

            } catch (\Exception $e) {
                 if ($e instanceof BadRequestHttpException) {
                    throw $e;
                }
                throw new BadRequestHttpException('Error processing documents: ' . $e->getMessage(), $e);
            }

        // Link Delivery to User
        $user->setDelivery($delivery);

        // Save user (Delivery will be saved via cascade)
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Send verification email
        $this->emailVerificationService->sendVerificationEmail($user);

        // Return success response instead of JWT auto-login
        return [
            'success' => true,
            'message' => 'Inscription réussie. Un email de vérification a été envoyé à votre adresse email.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'isEmailVerified' => $user->isEmailVerified(),
                'roles' => $user->getRoles(),
                'userType' => 'delivery',
                'isActive' => $user->getActive(),
                'delivery' => [
                    'vehicleType' => $delivery->getVehicleType(),
                    'vehiclePlate' => $delivery->getVehiclePlate(),
                    'isOnline' => $delivery->getIsOnline(),
                    'totalDeliveries' => $delivery->getTotalDeliveries(),
                    'averageRating' => $delivery->getAverageRating(),
                    'totalEarnings' => $delivery->getTotalEarnings()
                ]
            ],
            'requiresEmailVerification' => true
        ];
        } catch (ConflictHttpException | BadRequestHttpException $e) {
            // Re-throw HTTP exceptions as-is
            throw $e;
        } catch (\TypeError $e) {
            // Handle type errors (e.g., accessing undefined properties)
            throw new BadRequestHttpException('Invalid request data: ' . $e->getMessage(), $e);
        } catch (\Exception $e) {
            // Log the actual error and throw a more user-friendly error
            // Include the exception class and message for debugging
            $errorMessage = 'Registration failed';
            if ($e->getMessage()) {
                $errorMessage .= ': ' . $e->getMessage();
            }
            throw new BadRequestHttpException($errorMessage, $e);
        }
    }
}
