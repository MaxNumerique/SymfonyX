<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user)
    {
        // Vérifie si l'utilisateur passé à cette méthode est bien une instance de la classe "User"

        if (!$user instanceof User) {
            return;
        }

        // Si l'utilisateur n'a pas confirmé son compte, on empêche la connexion
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException("Votre compte n'a pas encore été confirmé");
        }
    }

    public function checkPostAuth(UserInterface $user)
    {
        // TODO: Implement checkPostAuth() method.
    }
}
