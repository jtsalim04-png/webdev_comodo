<?php

namespace App\Service;

use App\Entity\Ticket;

/**
 * Shared QR payload for web orders and mobile API tickets.
 */
class TicketQrService
{
    public function generatePayload(Ticket $ticket): string
    {
        $payload = [
            'ticketId' => $ticket->getId(),
            'eventId' => $ticket->getEvent()?->getId(),
            'customerId' => $ticket->getCustomer()?->getId(),
            'price' => $ticket->getPrice(),
            'status' => $ticket->getStatus(),
            'purchaseDate' => $ticket->getPurchaseDate()?->format(\DateTimeInterface::ATOM),
            'nonce' => bin2hex(random_bytes(8)),
            'issuedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        return json_encode($payload, JSON_UNESCAPED_SLASHES);
    }
}
