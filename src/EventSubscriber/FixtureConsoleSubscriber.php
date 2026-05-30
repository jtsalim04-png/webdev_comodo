<?php

namespace App\EventSubscriber;

use App\Service\FixtureLoadState;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FixtureConsoleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FixtureLoadState $fixtureLoadState,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onCommand',
            ConsoleEvents::TERMINATE => 'onTerminate',
        ];
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        if ($event->getCommand()?->getName() === 'doctrine:fixtures:load') {
            $this->fixtureLoadState->begin();
        }
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        if ($event->getCommand()?->getName() === 'doctrine:fixtures:load') {
            $this->fixtureLoadState->end();
        }
    }
}
