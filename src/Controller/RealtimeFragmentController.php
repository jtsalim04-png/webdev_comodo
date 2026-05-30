<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Service\AdminDashboardDataProvider;
use App\Service\PurchaseNotificationProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/realtime/fragment')]
class RealtimeFragmentController extends AbstractController
{
    #[Route('/admin_notifications', name: 'realtime_fragment_admin_notifications', methods: ['GET'])]
    public function adminNotifications(PurchaseNotificationProvider $notificationProvider): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('realtime/_admin_notifications.html.twig', [
            'notifications' => $notificationProvider->getNotifications(),
        ]);
    }

    #[Route('/admin_dashboard', name: 'realtime_fragment_admin_dashboard', methods: ['GET'])]
    public function adminDashboard(AdminDashboardDataProvider $dashboardDataProvider): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('realtime/_admin_dashboard.html.twig', $dashboardDataProvider->getDashboardData());
    }

    #[Route('/admin_tickets', name: 'realtime_fragment_admin_tickets', methods: ['GET'])]
    public function adminTickets(TicketRepository $ticketRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('realtime/_admin_tickets.html.twig', [
            'tickets' => $ticketRepository->findBy([], ['purchaseDate' => 'DESC']),
        ]);
    }

    #[Route('/admin_users', name: 'realtime_fragment_admin_users', methods: ['GET'])]
    public function adminUsers(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('realtime/_admin_users.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/admin_events', name: 'realtime_fragment_admin_events', methods: ['GET'])]
    public function adminEvents(EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('realtime/_admin_events.html.twig', [
            'events' => $eventRepository->findAll(),
        ]);
    }

    #[Route('/admin_activity_logs', name: 'realtime_fragment_admin_activity_logs', methods: ['GET'])]
    public function adminActivityLogs(
        Request $request,
        ActivityLogRepository $activityLogRepository,
        UserRepository $userRepository,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $userId = $request->query->get('user');
        $action = $request->query->get('action');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        $startDateObj = is_string($startDate) && $startDate !== '' ? new \DateTimeImmutable($startDate) : null;
        $endDateObj = is_string($endDate) && $endDate !== '' ? new \DateTimeImmutable($endDate.' 23:59:59') : null;

        $user = null;
        if ($userId !== null && $userId !== '') {
            $user = $userRepository->find((int) $userId);
        }

        return $this->render('realtime/_admin_activity_logs.html.twig', [
            'logs' => $activityLogRepository->findByFilters(
                $user,
                is_string($action) ? $action : null,
                $startDateObj,
                $endDateObj,
                200
            ),
        ]);
    }

    #[Route('/user_dashboard', name: 'realtime_fragment_user_dashboard', methods: ['GET'])]
    public function userDashboard(EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ORGANIZER')) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('realtime/_user_dashboard.html.twig', [
            'events' => $eventRepository->findUpcoming(12),
        ]);
    }

    #[Route('/user_my_tickets', name: 'realtime_fragment_user_my_tickets', methods: ['GET'])]
    public function userMyTickets(TicketRepository $ticketRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        return $this->render('realtime/_user_my_tickets.html.twig', [
            'tickets' => $ticketRepository->findBy(
                ['customer' => $user],
                ['purchaseDate' => 'DESC']
            ),
        ]);
    }

    #[Route('/order_list', name: 'realtime_fragment_order_list', methods: ['GET'])]
    public function orderList(EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('realtime/_order_list.html.twig', [
            'events' => $eventRepository->findBy([], ['eventDate' => 'ASC']),
        ]);
    }

    #[Route('/organizer_dashboard', name: 'realtime_fragment_organizer_dashboard', methods: ['GET'])]
    public function organizerDashboard(EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');

        if ($this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $currentUser = $this->getUser();
        $events = $eventRepository->findBy(['organizer' => $currentUser]);
        $totalTickets = 0;
        foreach ($events as $event) {
            $totalTickets += count($event->getTickets());
        }

        return $this->render('realtime/_organizer_dashboard.html.twig', [
            'stats' => [
                'total_events' => count($events),
                'total_tickets' => $totalTickets,
            ],
            'events' => $events,
        ]);
    }
}
