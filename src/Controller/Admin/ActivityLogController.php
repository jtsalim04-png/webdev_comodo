<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/activity-logs')]
class ActivityLogController extends AbstractController
{
    #[Route('', name: 'admin_activity_logs', methods: ['GET'])]
    public function list(
        Request $request,
        ActivityLogRepository $activityRepo,
        UserRepository $userRepo
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Get filter parameters
        $userId = $request->query->get('user');
        $action = $request->query->get('action');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        // Parse dates
        $startDateObj = $startDate ? new \DateTimeImmutable($startDate) : null;
        $endDateObj = $endDate ? new \DateTimeImmutable($endDate . ' 23:59:59') : null;

        // Get user object if user filter is set
        $user = null;
        if ($userId) {
            $user = $userRepo->find($userId);
        }

        // Get all users for filter dropdown
        $users = $userRepo->findAll();

        // Get filtered logs
        $logs = $activityRepo->findByFilters($user, $action, $startDateObj, $endDateObj, 200);

        // Get available actions for filter
        $availableActions = ['LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'DELETE'];

        return $this->render('admin/activity-log.html.twig', [
            'logs' => $logs,
            'users' => $users,
            'availableActions' => $availableActions,
            'selectedUser' => $userId,
            'selectedAction' => $action,
            'selectedStartDate' => $startDate,
            'selectedEndDate' => $endDate,
        ]);
    }
}

