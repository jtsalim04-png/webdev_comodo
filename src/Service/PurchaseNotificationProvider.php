<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Repository\TicketRepository;

class PurchaseNotificationProvider
{
    public function __construct(
        private TicketRepository $ticketRepository,
    ) {
    }

    /**
     * @return list<array{id: int, user: string, event: string, price: float, text: string, status: string}>
     */
    public function getNotifications(int $limit = 20): array
    {
        $notifications = [];
        foreach ($this->ticketRepository->findRecentPurchases($limit) as $ticket) {
            $notifications[] = $this->buildNotification($ticket);
        }

        return $notifications;
    }

    /**
     * @return array{id: int, user: string, event: string, price: float, text: string, status: string}
     */
    private function buildNotification(Ticket $ticket): array
    {
        $customer = $ticket->getCustomer();
        $userName = trim(sprintf(
            '%s %s',
            $customer?->getFirstName() ?? '',
            $customer?->getLastName() ?? ''
        ));
        if ($userName === '') {
            $userName = $customer?->getEmail() ?? 'Unknown user';
        }

        return [
            'id' => (int) $ticket->getId(),
            'user' => $userName,
            'event' => $ticket->getEvent()?->getTitle() ?? 'Unknown event',
            'price' => (float) $ticket->getPrice(),
            'text' => $this->formatRelativeTime($ticket->getPurchaseDate()),
            'status' => (string) ($ticket->getStatus() ?? 'pending'),
        ];
    }

    private function formatRelativeTime(?\DateTimeInterface $dateTime): string
    {
        if (!$dateTime) {
            return 'recently';
        }

        $now = new \DateTimeImmutable();
        $then = \DateTimeImmutable::createFromInterface($dateTime);
        $seconds = max(0, $now->getTimestamp() - $then->getTimestamp());

        if ($seconds < 60) {
            return 'just now';
        }

        $minutes = (int) floor($seconds / 60);
        if ($minutes < 60) {
            return $minutes === 1 ? '1 minute ago' : sprintf('%d minutes ago', $minutes);
        }

        $hours = (int) floor($minutes / 60);
        if ($hours < 24) {
            return $hours === 1 ? '1 hour ago' : sprintf('%d hours ago', $hours);
        }

        return $then->format('M d, Y g:i A');
    }
}
