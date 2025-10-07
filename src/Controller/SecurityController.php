<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@EasyAdmin/page/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'favicon_path' => './assets/img/favico-bw.png',
            'page_title' => "ScoutReady App",
            'target_path' => $this->generateUrl('admin'),
            '_username_label' => "Votre nom d'utilisateur",
            '_password_label' => "Votre mot de passe",
            'forgot_password_enabled' => true,
            'remember_me_enabled' => true,
            'remember_me_parameter' => '_remember_me',
            'remember_me_checked' => false,
            '_remember_me_label' => 'Se souvenir de moi',
            'csrf_token_intention' => 'authenticate',
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
