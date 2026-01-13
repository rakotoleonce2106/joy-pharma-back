<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterCustomerProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private AuthenticationSuccessHandler $authenticationSuccessHandler
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        try {
            // $data is the User entity (or DTO if invalid)
             // Check if data is an array (likely due to invalid input format)
             if (is_array($data)) {
                 // Try to map array to entity manually if needed or throw error
                 throw new BadRequestHttpException('Invalid request format');
             }
             
             // If ApiPlatform has already deserialized to User entity
             if ($data instanceof User) {
                // Validate required fields (if not already handled by validation groups)
                 if (empty($data->getEmail())) {
                     throw new BadRequestHttpException('Email is required');
                 }
                 
                 // Support both plainPassword and password fields
                 if (empty($data->getPlainPassword()) && !empty($data->getPassword())) {
                     $data->setPlainPassword($data->getPassword());
                 }

                 if (empty($data->getPlainPassword())) {
                     throw new BadRequestHttpException('Password is required');
                 }

                 // Check for existing user
                 $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data->getEmail()]);
                 if ($existingUser) {
                     throw new ConflictHttpException('Email already exists');
                 }

                 // Set roles
                 $data->setRoles(['ROLE_USER']);
                 $data->setUserType('customer');
                 $data->setActive(true);

                 // Hash password
                 $hashedPassword = $this->passwordHasher->hashPassword($data, $data->getPlainPassword());
                 $data->setPassword($hashedPassword);
                 
                 // Handle Media Object (Image)
                 // If the image is passed as an IRI string in "image", Api Platform might have tried to set it
                 // But typically for custom processors we might need simpler handling. 
                 // Assuming standard ApiPlatform deserialization handled the relation correctly if properly mapped.
                 
                 // Persist user
                 $this->entityManager->persist($data);
                 $this->entityManager->flush();

                 // Generate JWT token
                 $jwtResponse = $this->authenticationSuccessHandler->handleAuthenticationSuccess($data);
                 
                 return $jwtResponse;
             }
             
             // Fallback if data is not a User entity (should normally be handled by deserializer)
             throw new BadRequestHttpException('Invalid data provided');

        } catch (ConflictHttpException | BadRequestHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Registration failed: ' . $e->getMessage(), $e);
        }
    }
}
