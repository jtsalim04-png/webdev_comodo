<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        // If the user is already logged in, redirect them based on role
        if ($this->getUser()) {
            $user = $this->getUser();
            $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];
            
            // Clear any stored target paths to prevent redirect issues
            $session = $request->getSession();
            $session->remove('_security.main.target_path');
            
            // Redirect based on role
            if (in_array('ROLE_ADMIN', $roles, true)) {
                return $this->redirectToRoute('admin_dashboard');
            } elseif (in_array('ROLE_ORGANIZER', $roles, true)) {
                return $this->redirectToRoute('organizer_dashboard');
            } else {
                return $this->redirectToRoute('user_dashboard');
            }
        }

        // Get the login error if any
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): Response
    {
        // Intercepted by Symfony automatically
        return $this->redirectToRoute('app_login');
    }
}

