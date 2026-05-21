<?php

namespace App\Controller\Organizer;

use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organizer')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'organizer_dashboard', methods: ['GET'])]
    #[Route('/dashboard', name: 'organizer_dashboard_alt', methods: ['GET'])]
    public function dashboard(EventRepository $eventRepo, TicketRepository $ticketRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');
        $this->denyIfAdmin();

        $currentUser = $this->getUser();

        // Get events organized by current user
        $events = $eventRepo->findBy(['organizer' => $currentUser]);
        
        // Count total tickets for events organized by this user
        $totalTickets = 0;
        foreach ($events as $event) {
            $totalTickets += count($event->getTickets());
        }

        $stats = [
            'total_events' => count($events),
            'total_tickets' => $totalTickets,
        ];

        return $this->render('organizer/dashboard.html.twig', [
            'stats' => $stats,
            'events' => $events,
        ]);
    }

    private function denyIfAdmin(): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Admins cannot access organizer pages.');
        }
    }
}

