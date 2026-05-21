<?php

namespace App\Controller\Organizer;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organizer/events')]
class EventController extends AbstractController
{
    #[Route('', name: 'organizer_events_list', methods: ['GET'])]
    public function list(EventRepository $eventRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');
        $this->denyIfAdmin();
        
        $currentUser = $this->getUser();
        $events = $eventRepository->findBy(['organizer' => $currentUser], ['eventDate' => 'DESC']);

        return $this->render('organizer/event/list.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'organizer_events_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');
        $this->denyIfAdmin();
        
        $event = new Event();
        $event->setOrganizer($this->getUser());
        $event->setCreatedBy($this->getUser());
        
        // Do not show organizer dropdown for organizer users; assign current user as organizer
        $form = $this->createForm(EventType::class, $event, ['include_organizer' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Event created successfully!');
            return $this->redirectToRoute('organizer_events_list');
        }

        return $this->render('organizer/event/form.html.twig', [
            'form' => $form,
            'title' => 'Create New Event',
        ]);
    }

    #[Route('/{id}/edit', name: 'organizer_events_edit', methods: ['GET', 'POST'])]
    public function edit(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');
        $this->denyIfAdmin();
        
        // Check if user owns this event
        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only edit your own events.');
        }

        // Prevent editing events created by admins
        $createdBy = $event->getCreatedBy();
        if ($createdBy !== null) {
            $createdByRole = $createdBy->getRole();
            $createdByRoles = $createdBy->getRoles();
            if ($createdByRole === 'Admin' || $createdByRole === 'ROLE_ADMIN' || in_array('ROLE_ADMIN', $createdByRoles)) {
                throw $this->createAccessDeniedException('You cannot edit events created by administrators.');
            }
        }

        // When editing, do not allow changing the organizer
        $form = $this->createForm(EventType::class, $event, ['include_organizer' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Event updated successfully!');
            return $this->redirectToRoute('organizer_events_list');
        }

        return $this->render('organizer/event/form.html.twig', [
            'form' => $form,
            'title' => 'Edit Event: ' . $event->getTitle(),
            'event' => $event,
        ]);
    }

    #[Route('/{id}/delete', name: 'organizer_events_delete', methods: ['POST'])]
    public function delete(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');
        $this->denyIfAdmin();
        
        // Check if user owns this event
        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only delete your own events.');
        }

        // Prevent deleting events created by admins
        $createdBy = $event->getCreatedBy();
        if ($createdBy !== null) {
            $createdByRole = $createdBy->getRole();
            $createdByRoles = $createdBy->getRoles();
            if ($createdByRole === 'Admin' || $createdByRole === 'ROLE_ADMIN' || in_array('ROLE_ADMIN', $createdByRoles)) {
                throw $this->createAccessDeniedException('You cannot delete events created by administrators.');
            }
        }

        if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Event deleted successfully!');
        } else {
            $this->addFlash('error', 'Invalid CSRF token!');
        }

        return $this->redirectToRoute('organizer_events_list');
    }

    #[Route('/{id}', name: 'organizer_events_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ORGANIZER');
        $this->denyIfAdmin();
        
        // Check if user owns this event
        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You can only view your own events.');
        }

        return $this->render('organizer/event/show.html.twig', [
            'event' => $event,
        ]);
    }

    private function denyIfAdmin(): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Admins cannot access organizer pages.');
        }
    }
}

