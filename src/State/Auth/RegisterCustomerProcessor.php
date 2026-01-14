<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use App\Entity\User;
use App\Service\EmailVerificationService;
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
        private AuthenticationSuccessHandler $authenticationSuccessHandler,
        private EmailVerificationService $emailVerificationService
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

                 $data->setActive(true);

                 // Set email as not verified initially
                 $data->setIsEmailVerified(false);

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

                 // Send verification email
                 $emailSent = $this->emailVerificationService->sendVerificationEmail($data);

                 if (!$emailSent) {
                     // If email sending fails, we should still allow registration but log the issue
                     // In a production environment, you might want to handle this differently
                 }

                 // Return success message instead of JWT token
                 // User needs to verify email before they can login
                 return [
                     'success' => true,
                     'message' => 'Inscription réussie. Un email de vérification a été envoyé à votre adresse email.',
                     'user' => [
                         'id' => $data->getId(),
                         'email' => $data->getEmail(),
                         'firstName' => $data->getFirstName(),
                         'lastName' => $data->getLastName(),
                         'isEmailVerified' => $data->isEmailVerified()
                     ],
                     'requiresEmailVerification' => true
                 ];
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
