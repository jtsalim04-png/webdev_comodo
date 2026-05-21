<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    #[Route('/dashboard', name: 'admin_dashboard_alt', methods: ['GET'])]
    public function dashboard(
        UserRepository $userRepo,
        EventRepository $eventRepo,
        TicketRepository $ticketRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Calculate total revenue from all tickets
        $allTickets = $ticketRepo->findAll();
        $totalRevenue = 0;
        foreach ($allTickets as $ticket) {
            $totalRevenue += (float) $ticket->getPrice();
        }

        $stats = [
            'total_users' => count($userRepo->findAll()),
            'total_events' => count($eventRepo->findAll()),
            'total_tickets' => count($allTickets),
            'total_revenue' => $totalRevenue,
            'organizers' => count($userRepo->findByRole('ROLE_ORGANIZER')),
            'users' => count($userRepo->findByRole('ROLE_USER')),
        ];

        $admins = count($userRepo->findByRole('ROLE_ADMIN'));
        $roleChartData = [
            'labels' => ['Admins', 'Organizers', 'Users'],
            'data' => [$admins, $stats['organizers'], $stats['users']],
        ];

        // Aggregate ticket sales per event
        $eventSales = [];
        foreach ($allTickets as $ticket) {
            $event = $ticket->getEvent();
            if (!$event) {
                continue;
            }

            $eventId = $event->getId();
            if (!isset($eventSales[$eventId])) {
                $eventSales[$eventId] = [
                    'title' => $event->getTitle(),
                    'revenue' => 0.0,
                    'tickets' => 0,
                ];
            }

            $eventSales[$eventId]['revenue'] += (float) $ticket->getPrice();
            $eventSales[$eventId]['tickets'] += 1;
        }

        // Take top 5 events by revenue
        usort($eventSales, fn ($a, $b) => $b['revenue'] <=> $a['revenue']);
        $topEvents = array_slice($eventSales, 0, 5);

        $eventSalesChart = [
            'labels' => array_map(fn ($event) => $event['title'], $topEvents),
            'revenue' => array_map(fn ($event) => round($event['revenue'], 2), $topEvents),
            'tickets' => array_map(fn ($event) => $event['tickets'], $topEvents),
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'roleChartData' => $roleChartData,
            'eventSalesChart' => $eventSalesChart,
        ]);
    }
}

