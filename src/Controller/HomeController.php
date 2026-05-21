<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(Request $request): Response
    {
        // Clear any stored target paths that might redirect to restricted routes
        $session = $request->getSession();
        $session->remove('_security.main.target_path');
        
        return $this->render('landing/index.html.twig');
    }

    #[Route('/about', name: 'about_us')]
    public function about(): Response
    {
        return $this->render('about/index.html.twig');
    }

    #[Route('/contact', name: 'contact_us')]
    public function contact(): Response
    {
        return $this->render('contact/index.html.twig');
    }
}

