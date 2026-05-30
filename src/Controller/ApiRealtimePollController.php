<?php

namespace App\Controller;

use App\Service\RealtimeVersionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * JWT polling for ComodoApp — same contract as GET /realtime/poll.
 */
#[Route('/api/realtime')]
class ApiRealtimePollController extends AbstractController
{
    #[Route('/poll', name: 'api_realtime_poll', methods: ['GET'])]
    public function poll(Request $request, RealtimeVersionService $realtimeVersionService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $since = (int) $request->query->get('since', 0);
        $version = $realtimeVersionService->getVersion();

        return $this->json([
            'version' => $version,
            'changed' => $since < $version,
        ]);
    }
}
