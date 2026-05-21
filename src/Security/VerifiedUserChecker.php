<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class VerifiedUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (
            $user instanceof User
            && !in_array('ROLE_ADMIN', $user->getRoles(), true)
            && !$user->isVerified()
        ) {
            throw new CustomUserMessageAccountStatusException('Please verify your email before logging in.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
