<?php

namespace App\Service;

use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;

class AdminDashboardDataProvider
{
    public function __construct(
        private UserRepository $userRepository,
        private EventRepository $eventRepository,
        private TicketRepository $ticketRepository,
    ) {
    }

    /**
     * @return array{stats: array<string, int|float>, roleChartData: array{labels: list<string>, data: list<int>}, eventSalesChart: array{labels: list<string>, revenue: list<float>, tickets: list<int>}}
     */
    public function getDashboardData(): array
    {
        $allTickets = $this->ticketRepository->findAll();
        $totalRevenue = 0.0;
        foreach ($allTickets as $ticket) {
            $totalRevenue += (float) $ticket->getPrice();
        }

        $stats = [
            'total_users' => count($this->userRepository->findAll()),
            'total_events' => count($this->eventRepository->findAll()),
            'total_tickets' => count($allTickets),
            'total_revenue' => $totalRevenue,
            'organizers' => count($this->userRepository->findByRole('ROLE_ORGANIZER')),
            'users' => count($this->userRepository->findByRole('ROLE_USER')),
        ];

        $admins = count($this->userRepository->findByRole('ROLE_ADMIN'));
        $roleChartData = [
            'labels' => ['Admins', 'Organizers', 'Users'],
            'data' => [$admins, $stats['organizers'], $stats['users']],
        ];

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

        usort($eventSales, fn (array $a, array $b) => $b['revenue'] <=> $a['revenue']);
        $topEvents = array_slice($eventSales, 0, 5);

        $eventSalesChart = [
            'labels' => array_map(fn (array $event) => $event['title'], $topEvents),
            'revenue' => array_map(fn (array $event) => round($event['revenue'], 2), $topEvents),
            'tickets' => array_map(fn (array $event) => $event['tickets'], $topEvents),
        ];

        return [
            'stats' => $stats,
            'roleChartData' => $roleChartData,
            'eventSalesChart' => $eventSalesChart,
        ];
    }
}
