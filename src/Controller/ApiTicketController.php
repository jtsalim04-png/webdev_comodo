<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use App\Service\TicketPurchaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tickets')]
class ApiTicketController extends AbstractController
{
    public function __construct(
        private TicketPurchaseService $ticketPurchaseService,
    ) {
    }

    #[Route('', name: 'api_tickets_list', methods: ['GET'])]
    public function list(TicketRepository $ticketRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();
        $tickets = $ticketRepository->findBy(
            ['customer' => $user],
            ['purchaseDate' => 'DESC']
        );

        return $this->json([
            'member' => array_map([$this, 'serializeTicket'], $tickets),
        ]);
    }

    #[Route('/{id}', name: 'api_tickets_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, TicketRepository $ticketRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ticket = $ticketRepository->find($id);
        if (!$ticket) {
            return $this->json(['message' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($ticket->getCustomer()?->getId() !== $user->getId()) {
            return $this->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($this->serializeTicket($ticket));
    }

    #[Route('', name: 'api_tickets_purchase', methods: ['POST'])]
    public function purchase(
        Request $request,
        EventRepository $eventRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $eventId = $data['eventId'] ?? null;
        if (!$eventId && isset($data['event']) && is_string($data['event'])) {
            if (preg_match('#/api/events/(\d+)#', $data['event'], $matches)) {
                $eventId = (int) $matches[1];
            }
        }

        if (!$eventId) {
            return $this->json(['message' => 'eventId is required'], Response::HTTP_BAD_REQUEST);
        }

        $event = $eventRepository->find((int) $eventId);
        if (!$event) {
            return $this->json(['message' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var User $user */
        $user = $this->getUser();
        $ticket = $this->ticketPurchaseService->purchase($user, $event);

        return $this->json(
            $this->serializeTicket($ticket),
            Response::HTTP_CREATED
        );
    }

    private function serializeTicket(Ticket $ticket): array
    {
        $event = $ticket->getEvent();
        $customer = $ticket->getCustomer();

        return [
            'id' => $ticket->getId(),
            'price' => $ticket->getPrice(),
            'status' => $ticket->getStatus(),
            'purchaseDate' => $ticket->getPurchaseDate()?->format(\DateTimeInterface::ATOM),
            'qrCodePath' => $ticket->getQrCodePath(),
            'seatLabel' => $event?->getSeatType(),
            'holderName' => trim(sprintf(
                '%s %s',
                $customer?->getFirstName() ?? '',
                $customer?->getLastName() ?? ''
            )),
            'holderEmail' => $customer?->getEmail(),
            'event' => $event ? [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'eventDate' => $event->getEventDate()?->format(\DateTimeInterface::ATOM),
                'location' => $event->getLocation(),
                'price' => $event->getPrice(),
                'seatType' => $event->getSeatType(),
            ] : null,
        ];
    }
}
