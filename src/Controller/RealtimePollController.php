<?php

namespace App\Controller;

use App\Service\RealtimeVersionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Session-authenticated polling for the web UI (mirrors mobile focus/pull refresh).
 */
#[Route('/realtime')]
class RealtimePollController extends AbstractController
{
    #[Route('/poll', name: 'realtime_poll', methods: ['GET'])]
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
