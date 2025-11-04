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
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // no-op
    }
}


