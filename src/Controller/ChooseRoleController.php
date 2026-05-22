<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

/**
 * Legacy route: Google sign-ups are organizers automatically.
 * Users who still have ROLE_UNSELECTED are upgraded on visit.
 */
class ChooseRoleController extends AbstractController
{
    #[Route('/choose-role', name: 'choose_role', methods: ['GET', 'POST'])]
    public function chooseRole(
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $roles = $user->getRoles();

        if (in_array('ROLE_UNSELECTED', $roles, true)) {
            $user->setRole('ROLE_ORGANIZER');
            $entityManager->flush();
            $tokenStorage->setToken(new PostAuthenticationToken($user, 'main', $user->getRoles()));

            return $this->redirectToRoute('organizer_dashboard');
        }

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return $this->redirectToRoute('admin_dashboard');
        }

        if (in_array('ROLE_ORGANIZER', $roles, true)) {
            return $this->redirectToRoute('organizer_dashboard');
        }

        return $this->redirectToRoute('user_dashboard');
    }
}
