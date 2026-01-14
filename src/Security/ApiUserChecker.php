<?php

namespace App\Security;

use App\Exception\ApiException;
use App\Entity\User;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Block inactive delivery accounts from logging in
        if (in_array('ROLE_DELIVER', $user->getRoles(), true) && !$user->getActive()) {
            throw new ApiException(
                'Your delivery account is awaiting activation.',
                ApiException::REQUEST_ACTIVATION,
                401
            );
        }

        // Block users who haven't verified their email
        if (!$user->isEmailVerified()) {
            throw new ApiException(
                'Votre adresse email n\'est pas vérifiée. Veuillez vérifier votre email avant de vous connecter.',
                ApiException::EMAIL_NOT_VERIFIED,
                401
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // no-op
    }
}


