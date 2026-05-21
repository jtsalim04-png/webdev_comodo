<?php

namespace App\Controller\Admin;

use App\Entity\Ticket;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/ticket')]
final class TicketController extends AbstractController
{
    #[Route(name: 'app_ticket_index', methods: ['GET'])]
    public function index(TicketRepository $ticketRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('/admin/ticket/index.html.twig', [
            'tickets' => $ticketRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_ticket_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $ticket = new Ticket();
        // lock_price => true so price is fixed to event at purchase time
        $form = $this->createForm(TicketType::class, $ticket, ['lock_price' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ticket->setPurchaseDate(new \DateTimeImmutable());
            // Auto-set price from event (fixed, not editable)
            if ($ticket->getEvent()) {
                $ticket->setPrice($ticket->getEvent()->getPrice());
            }

            $entityManager->persist($ticket);
            // First flush so we have an ID for the QR payload
            $entityManager->flush();

            // Generate JSON payload for QR (kept in DB to avoid storing images)
            $qrData = $this->generateQrCodeData($ticket);
            $ticket->setQrCodePath($qrData);
            $entityManager->flush();

            $this->addFlash('success', 'Ticket created successfully.');
            return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('/admin/ticket/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ticket_show', methods: ['GET'])]
    public function show(Ticket $ticket): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('/admin/ticket/show.html.twig', [
            'ticket' => $ticket,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ticket_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Keep price in sync with selected event
            if ($ticket->getEvent()) {
                $ticket->setPrice($ticket->getEvent()->getPrice());
            }

            // Refresh QR payload in case event/customer/status changed
            $qrData = $this->generateQrCodeData($ticket);
            $ticket->setQrCodePath($qrData);
            $entityManager->flush();

            $this->addFlash('success', 'Ticket updated successfully.');
            return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('/admin/ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ticket_delete', methods: ['POST'])]
    public function delete(Request $request, Ticket $ticket, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        if ($this->isCsrfTokenValid('delete' . $ticket->getId(), $request->request->get('_token'))) {
            $entityManager->remove($ticket);
            $entityManager->flush();
            $this->addFlash('success', 'Ticket deleted successfully.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_ticket_index', [], Response::HTTP_SEE_OTHER);
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

