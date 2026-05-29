<?php

namespace App\Controller\Api\Admin;

use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class ApiAdminDashboardController extends AbstractApiAdminController
{
    #[Route('/dashboard', name: 'api_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        UserRepository $userRepository,
        EventRepository $eventRepository,
        TicketRepository $ticketRepository,
    ): JsonResponse {
        $this->denyUnlessAdmin();

        $allTickets = $ticketRepository->findAll();
        $totalRevenue = 0.0;
        $eventSales = [];

        foreach ($allTickets as $ticket) {
            if ($ticket->getStatus() !== 'confirmed') {
                continue;
            }

            $totalRevenue += (float) $ticket->getPrice();

            $event = $ticket->getEvent();
            if (!$event) {
                continue;
            }

            $eventId = $event->getId();
            if (!isset($eventSales[$eventId])) {
                $eventSales[$eventId] = [
                    'eventId' => $eventId,
                    'title' => $event->getTitle(),
                    'revenue' => 0.0,
                    'tickets' => 0,
                ];
            }

            $eventSales[$eventId]['revenue'] += (float) $ticket->getPrice();
            $eventSales[$eventId]['tickets'] += 1;
        }

        usort($eventSales, fn (array $a, array $b) => $b['revenue'] <=> $a['revenue']);
        $topEvents = array_slice(array_values(array_map(static function (array $row): array {
            $row['revenue'] = round($row['revenue'], 2);

            return $row;
        }, $eventSales)), 0, 5);

        return $this->json([
            'stats' => [
                'totalUsers' => count($userRepository->findAll()),
                'totalEvents' => count($eventRepository->findAll()),
                'totalTickets' => count($allTickets),
                'totalRevenue' => round($totalRevenue, 2),
            ],
            'roleChart' => [
                'labels' => ['User', 'Organizer', 'Admin'],
                'data' => [
                    count($userRepository->findByRole('ROLE_USER')),
                    count($userRepository->findByRole('ROLE_ORGANIZER')),
                    count($userRepository->findByRole('ROLE_ADMIN')),
                ],
            ],
            'topEvents' => $topEvents,
        ]);
    }
}
