<?php

namespace App\Twig;

use App\Service\RealtimeVersionService;
use Twig\Attribute\AsTwigFunction;

final class RealtimeExtension
{
    public function __construct(
        private RealtimeVersionService $realtimeVersionService,
    ) {
    }

    #[AsTwigFunction('realtime_version')]
    public function getVersion(): int
    {
        return $this->realtimeVersionService->getVersion();
    }
}
