<?php

namespace App\Twig;

use App\Service\RealtimeVersionService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class RealtimeExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private RealtimeVersionService $realtimeVersionService,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'realtime_version' => $this->realtimeVersionService->getVersion(),
        ];
    }
}
