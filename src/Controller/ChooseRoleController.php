<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class ChooseRoleController extends AbstractController
{
    #[Route('/choose-role', name: 'choose_role', methods: ['GET', 'POST'])]
    public function chooseRole(
        Request $request,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $roles = $user->getRoles();
        if (!in_array('ROLE_UNSELECTED', $roles, true)) {
            // Existing users should never see this screen.
            if (in_array('ROLE_ORGANIZER', $roles, true)) {
                return $this->redirectToRoute('organizer_dashboard');
            }
            return $this->redirectToRoute('user_dashboard');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('choose_role', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Invalid request. Please try again.');
                return $this->redirectToRoute('choose_role');
            }

            $requestedRole = (string) $request->request->get('role');
            $requestedRole = str_starts_with($requestedRole, 'ROLE_') ? $requestedRole : '';

            if (!in_array($requestedRole, ['ROLE_USER', 'ROLE_ORGANIZER'], true)) {
                $this->addFlash('error', 'Invalid role selection.');
                return $this->redirectToRoute('choose_role');
            }

            $user->setRole($requestedRole);
            $entityManager->flush();

            // Ensure the current session token sees the updated role immediately.
            $tokenStorage->setToken(new PostAuthenticationToken($user, 'main', $user->getRoles()));

            if ($requestedRole === 'ROLE_ORGANIZER') {
                return $this->redirectToRoute('organizer_dashboard');
            }

            return $this->redirectToRoute('user_dashboard');
        }

        return $this->render('security/choose_role.html.twig', [
            'pending_email' => $user->getEmail(),
        ]);
    }
}

