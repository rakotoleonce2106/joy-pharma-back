<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
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
        if (in_array('ROLE_DELIVER', $user->getRoles(), true) && !$user->isActive()) {
            throw new CustomUserMessageAuthenticationException('Your delivery account is awaiting activation.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // no-op
    }
}


