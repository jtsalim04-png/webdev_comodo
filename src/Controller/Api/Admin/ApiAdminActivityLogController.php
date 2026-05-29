<?php

namespace App\Controller\Api\Admin;

use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/activity-logs')]
class ApiAdminActivityLogController extends AbstractApiAdminController
{
    private const AVAILABLE_ACTIONS = ['LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'DELETE'];

    #[Route('', name: 'api_admin_activity_logs', methods: ['GET'])]
    public function list(
        Request $request,
        ActivityLogRepository $activityLogRepository,
        UserRepository $userRepository,
    ): JsonResponse {
        $this->denyUnlessAdmin();

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

        $logs = $activityLogRepository->findByFilters(
            $user,
            is_string($action) ? $action : null,
            $startDateObj,
            $endDateObj,
            200
        );

        return $this->json([
            'availableActions' => self::AVAILABLE_ACTIONS,
            'logs' => array_map(static function ($log): array {
                return [
                    'id' => $log->getId(),
                    'action' => $log->getAction(),
                    'userId' => $log->getUser()?->getId(),
                    'username' => $log->getUsername(),
                    'role' => $log->getRole(),
                    'targetData' => $log->getTargetData(),
                    'description' => $log->getDescription(),
                    'createdAt' => $log->getCreatedAt()?->format(\DateTimeInterface::ATOM),
                ];
            }, $logs),
        ]);
    }
}
