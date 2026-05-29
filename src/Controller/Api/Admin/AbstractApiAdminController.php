<?php

namespace App\Controller\Api\Admin;

use App\Entity\Ticket;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractApiAdminController extends AbstractController
{
    protected function denyUnlessAdmin(): void
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
    }

    protected function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'role' => $user->getRole(),
            'isActive' => $user->getIsActive(),
        ];
    }

    protected function serializeTicket(Ticket $ticket): array
    {
        $event = $ticket->getEvent();
        $customer = $ticket->getCustomer();

        return [
            'id' => $ticket->getId(),
            'status' => $ticket->getStatus(),
            'price' => (float) $ticket->getPrice(),
            'purchaseDate' => $ticket->getPurchaseDate()?->format(\DateTimeInterface::ATOM),
            'holderName' => trim(sprintf(
                '%s %s',
                $customer?->getFirstName() ?? '',
                $customer?->getLastName() ?? ''
            )),
            'holderEmail' => $customer?->getEmail(),
            'qrCodePath' => $ticket->getQrCodePath(),
            'event' => $event ? [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'eventDate' => $event->getEventDate()?->format(\DateTimeInterface::ATOM),
                'location' => $event->getLocation(),
                'price' => (float) $event->getPrice(),
                'seatType' => $event->getSeatType(),
            ] : null,
        ];
    }

    /**
     * @param list<string> $allowed
     */
    protected function isAllowedRole(?string $role, array $allowed = ['ROLE_USER', 'ROLE_ORGANIZER', 'ROLE_ADMIN']): bool
    {
        return $role !== null && $role !== '' && in_array($role, $allowed, true);
    }
}
