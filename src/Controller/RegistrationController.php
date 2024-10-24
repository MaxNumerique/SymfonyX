<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, EmailService $emailService): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('plainPassword')->getData();
            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $user->setName($form->get('name')->getData());
            $user->setLastName($form->get('last_name')->getData());
            $user->setPseudo($form->get('pseudo')->getData());

            // générer un token de confirmation unique
            $token = Uuid::v4()->toRfc4122();
            $user->setToken($token);


            $entityManager->persist($user);
            $entityManager->flush();

            // envoyer un email de confirmation
            $confirmationLink = $this->generateUrl('app_confirm_email', [
                'token' => $token
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            //Envoyer l'email avec le lien de confirmation
            $emailService->sendEmail(
                $_ENV['MAIL_SENDER'],
                $user->getEmail(),
                'Veuillez confirmer votre inscription',
                'Bonjour ' . $user->getName() . ', <br><br>Veuillez cliquer sur ce lien pour confirmer votre inscription: <br> <a href="' . $confirmationLink . '">' . "Confirmer mon inscription" . '</a>'
            );

            $this->addFlash('success', 'Un email de confirmation vous a été envoyé');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
