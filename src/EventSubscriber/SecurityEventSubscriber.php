<?php

namespace App\EventSubscriber;

use App\Entity\ActivityLog;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SecurityEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
            LogoutEvent::class => 'onLogout',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        
        $log = new ActivityLog();
        $log->setAction('LOGIN');
        $log->setDescription('User logged in');
        $log->setCreatedAt(new \DateTimeImmutable());

        if ($user instanceof User) {
            $log->setUser($user);
            $log->setUsername($user->getEmail());
            $log->setRole($user->getRoles()[0] ?? 'ROLE_USER');
            $log->setTargetData('User: ' . $user->getEmail() . ' (ID: ' . $user->getId() . ')');
        } else {
            $log->setRole('ANONYMOUS');
            $log->setUsername('ANONYMOUS');
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        
        if (null === $token) {
            return;
        }

        $user = $token->getUser();
        
        $log = new ActivityLog();
        $log->setAction('LOGOUT');
        $log->setDescription('User logged out');
        $log->setCreatedAt(new \DateTimeImmutable());

        if ($user instanceof User) {
            $log->setUser($user);
            $log->setUsername($user->getEmail());
            $log->setRole($user->getRoles()[0] ?? 'ROLE_USER');
            $log->setTargetData('User: ' . $user->getEmail() . ' (ID: ' . $user->getId() . ')');
        } else {
            $log->setRole('ANONYMOUS');
            $log->setUsername('ANONYMOUS');
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Prevent sensitive pages from being served from browser history cache after logout.
        if ($request->getMethod() !== 'GET' || $request->getRequestFormat() !== 'html') {
            return;
        }

        if (!$this->security->getUser()) {
            return;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
    }
}

