<?php

namespace App\Controller\Api\Admin;

use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use App\Repository\UserRepository;
use App\Service\TicketPurchaseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/tickets')]
class ApiAdminTicketController extends AbstractApiAdminController
{
    public function __construct(
        private TicketPurchaseService $ticketPurchaseService,
    ) {
    }

    #[Route('', name: 'api_admin_tickets_list', methods: ['GET'])]
    public function list(TicketRepository $ticketRepository): JsonResponse
    {
        $this->denyUnlessAdmin();

        $tickets = $ticketRepository->findBy([], ['purchaseDate' => 'DESC']);

        return $this->json(array_map([$this, 'serializeTicket'], $tickets));
    }

    #[Route('', name: 'api_admin_tickets_create', methods: ['POST'])]
    public function create(
        Request $request,
        EventRepository $eventRepository,
        UserRepository $userRepository,
    ): JsonResponse {
        $this->denyUnlessAdmin();

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['message' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
        }

        $eventId = $data['eventId'] ?? null;
        $customerId = $data['customerId'] ?? null;
        if (!$eventId || !$customerId) {
            return $this->json(['message' => 'eventId and customerId are required'], Response::HTTP_BAD_REQUEST);
        }

        $event = $eventRepository->find((int) $eventId);
        if (!$event) {
            return $this->json(['message' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        $customer = $userRepository->find((int) $customerId);
        if (!$customer) {
            return $this->json(['message' => 'Customer not found'], Response::HTTP_NOT_FOUND);
        }

        $status = $data['status'] ?? 'confirmed';
        if (!is_string($status) || $status === '') {
            $status = 'confirmed';
        }

        $ticket = $this->ticketPurchaseService->createForCustomer($customer, $event, $status);

        return $this->json($this->serializeTicket($ticket), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_admin_tickets_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, TicketRepository $ticketRepository): JsonResponse
    {
        $this->denyUnlessAdmin();

        $ticket = $ticketRepository->find($id);
        if (!$ticket) {
            return $this->json(['message' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeTicket($ticket));
    }

    #[Route('/{id}', name: 'api_admin_tickets_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, TicketRepository $ticketRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyUnlessAdmin();

        $ticket = $ticketRepository->find($id);
        if (!$ticket) {
            return $this->json(['message' => 'Ticket not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($ticket);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
