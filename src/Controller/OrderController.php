<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Ticket;
use App\Repository\EventRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/orders')]
class OrderController extends AbstractController
{
    #[Route('', name: 'order_list', methods: ['GET'])]
    public function list(EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $events = $eventRepository->findBy([], ['eventDate' => 'ASC']);

        return $this->render('order/list.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/my-tickets', name: 'order_my_tickets', methods: ['GET'])]
    public function myTickets(TicketRepository $ticketRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $tickets = $ticketRepository->findBy(
            ['customer' => $user],
            ['purchaseDate' => 'DESC']
        );

        return $this->render('order/my_tickets.html.twig', [
            'tickets' => $tickets,
        ]);
    }

    #[Route('/{id}', name: 'order_show', methods: ['GET'])]
    public function show(Event $event, TicketRepository $ticketRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $ticket = null;
        $user = $this->getUser();
        if ($user) {
            $ticket = $ticketRepository->findOneBy(
                ['event' => $event, 'customer' => $user],
                ['purchaseDate' => 'DESC']
            );
        }

        return $this->render('order/show.html.twig', [
            'event' => $event,
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}/purchase', name: 'order_purchase', methods: ['POST'])]
    public function purchase(
        Event $event,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('purchase' . $event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid purchase request.');
            return $this->redirectToRoute('order_show', ['id' => $event->getId()]);
        }

        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $ticket = new Ticket();
        $ticket->setEvent($event);
        $ticket->setCustomer($user);
        $ticket->setPrice($event->getPrice());
        $ticket->setStatus('confirmed'); // payment completed instantly
        $ticket->setPurchaseDate(new \DateTimeImmutable());

        $em->persist($ticket);
        $em->flush(); // first flush to obtain ticket ID

        // Generate QR payload JSON similar to admin flow
        $qrData = $this->generateQrCodeData($ticket);
        $ticket->setQrCodePath($qrData);
        $em->flush();

        $this->addFlash('success', 'Ticket purchased successfully! Your payment is marked as completed.');

        return $this->redirectToRoute('order_show', ['id' => $event->getId()]);
    }

    private function generateQrCodeData(Ticket $ticket): string
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


