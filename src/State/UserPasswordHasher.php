<?php
// api/src/State/UserPasswordHasher.php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use ApiPlatform\Metadata\Post;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;

final readonly class UserPasswordHasher implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $processor,
        private UserPasswordHasherInterface $passwordHasher,
        private AuthenticationSuccessHandler $successHandler
    )
    {
    }



    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $isPostUser = $data instanceof User && $operation instanceof Post;

        if (!$data instanceof User || !$data->getPlainPassword()) {
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }
        dd($data->getPlainPassword());
        // Hashage du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword(
            $data,
            $data->getPlainPassword()
        );
        
        $data->setPassword($hashedPassword);
        $data->eraseCredentials();

        $processedUser = $this->processor->process($data, $operation, $uriVariables, $context);

        if ($isPostUser) {
            return $this->successHandler->handleAuthenticationSuccess($data);
        }

        return $processedUser;
    }

}
