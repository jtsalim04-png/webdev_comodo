<?php

namespace App\EventSubscriber;

use App\Service\AppUrlService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

final class RouterContextSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AppUrlService $appUrlService,
        private readonly RouterInterface $router,
        private readonly KernelInterface $kernel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $baseUrl = $this->appUrlService->getBaseUrl();
        if ($baseUrl === null) {
            return;
        }

        if ($this->kernel->getEnvironment() !== 'prod' && !getenv('RAILWAY_PUBLIC_DOMAIN')) {
            return;
        }

        $parts = parse_url($baseUrl);
        if ($parts === false || empty($parts['host'])) {
            return;
        }

        $context = $this->router->getContext();
        $context->setHost($parts['host']);
        $context->setScheme($parts['scheme'] ?? 'https');

        if (!empty($parts['port'])) {
            $port = (int) $parts['port'];
            $context->setHttpPort($port);
            $context->setHttpsPort($port);
        }
    }
}
