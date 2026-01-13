<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Delivery;
use App\Entity\MediaObject;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterDeliveryProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private AuthenticationSuccessHandler $authenticationSuccessHandler
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
            if (!property_exists($data, 'residenceDocument') || $data->residenceDocument === null) {
                throw new BadRequestHttpException('Residence document is required');
            }

            if (!property_exists($data, 'vehicleDocument') || $data->vehicleDocument === null) {
                throw new BadRequestHttpException('Vehicle document is required');
            }
            
            // Check if files are UploadedFile instances
            if (!($data->residenceDocument instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)) {
                throw new BadRequestHttpException('Residence document must be a valid file');
            }

            if (!($data->vehicleDocument instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)) {
                throw new BadRequestHttpException('Vehicle document must be a valid file');
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
            // Check if files are valid before setting
            try {
                if (!$data->residenceDocument->isValid()) {
                    throw new BadRequestHttpException('Residence document is not valid');
                }

                if (!$data->vehicleDocument->isValid()) {
                    throw new BadRequestHttpException('Vehicle document is not valid');
                }

                // Create MediaObject for residence document
                $residenceMediaObject = new MediaObject();
                $residenceMediaObject->setFile($data->residenceDocument);
                $residenceMediaObject->setMapping('media_object');
                $this->entityManager->persist($residenceMediaObject);
                $delivery->setResidenceDocument($residenceMediaObject);

                // Create MediaObject for vehicle document
                $vehicleMediaObject = new MediaObject();
                $vehicleMediaObject->setFile($data->vehicleDocument);
                $vehicleMediaObject->setMapping('media_object');
                $this->entityManager->persist($vehicleMediaObject);
                $delivery->setVehicleDocument($vehicleMediaObject);
            } catch (\Exception $fileException) {
                if ($fileException instanceof BadRequestHttpException) {
                    throw $fileException;
                }
                throw new BadRequestHttpException('Error processing documents: ' . $fileException->getMessage(), $fileException);
            }

        // Link Delivery to User
        $user->setDelivery($delivery);

        // Save user (Delivery will be saved via cascade)
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Generate JWT token
        $jwtResponse = $this->authenticationSuccessHandler->handleAuthenticationSuccess($user);
        
        // Extract token data from the response
        $responseData = json_decode($jwtResponse->getContent(), true);

        // Return response with token and user data (like login)
        return $responseData;
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
