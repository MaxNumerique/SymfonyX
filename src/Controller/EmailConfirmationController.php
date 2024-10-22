<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmailConfirmationController extends AbstractController
{
    #[Route('/confirm-email/{token}', name: 'app_confirm_email')]
    public function index(
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        //Rechercher l'utilisateur avec le token
        $user = $userRepository->findOneBy(['token' => $token]);

        if (!$user) {
            throw $this->createNotFoundException('Ce token de confirmation est invalide!');
        }

        // Activer le compte de l'utilisateur
        $user->setToken(''); //On supprime le token après utilisation
        $user->setVerified(true); //On active le compte
        $em->flush();

        return $this->render('email_confirmation/index.html.twig', [
            'user' => $user,
        ]);
    }
}
