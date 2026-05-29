<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class TicketPurchaseService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TicketQrService $ticketQrService,
    ) {
    }

    public function purchase(User $customer, Event $event): Ticket
    {
        return $this->createForCustomer($customer, $event, 'confirmed');
    }

    public function createForCustomer(User $customer, Event $event, string $status = 'confirmed'): Ticket
    {
        $ticket = new Ticket();
        $ticket->setEvent($event);
        $ticket->setCustomer($customer);
        $ticket->setPrice((string) $event->getPrice());
        $ticket->setStatus($status);
        $ticket->setPurchaseDate(new \DateTimeImmutable());

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        $ticket->setQrCodePath($this->ticketQrService->generatePayload($ticket));
        $this->entityManager->flush();

        return $ticket;
    }
}
