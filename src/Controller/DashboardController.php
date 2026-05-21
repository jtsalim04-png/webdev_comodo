<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'user_dashboard', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ORGANIZER')) {
            throw $this->createAccessDeniedException('Only regular users can access the user dashboard.');
        }

        $events = $eventRepository->findUpcoming(12);

        return $this->render('dashboard/index.html.twig', [
            'events' => $events,
        ]);
    }
}

