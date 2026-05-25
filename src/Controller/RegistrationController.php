<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService
    ): Response
    {
        if ($this->getUser()) {
            $user = $this->getUser();
            $roles = method_exists($user, 'getRoles') ? $user->getRoles() : [];

            if (in_array('ROLE_ADMIN', $roles, true)) {
                return $this->redirectToRoute('admin_dashboard');
            }
            if (in_array('ROLE_ORGANIZER', $roles, true)) {
                return $this->redirectToRoute('organizer_dashboard');
            }
            return $this->redirectToRoute('user_dashboard');
        }
        
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // Enforce password policy: minimum 6 chars, letters/numbers only (no spaces/special chars)
            if (!$this->isPasswordPolicySatisfied($plainPassword)) {
                $form->get('plainPassword')->addError(new FormError(
                    'Password must be at least 6 characters and contain only letters and numbers (no spaces or special characters).'
                ));
            } else {
                // encode the plain password
                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
                // Email registration is always a standard user account.
                $user->setRole('ROLE_USER');
                $verificationToken = $emailVerificationService->generateVerificationToken();
                $user->setVerificationToken($verificationToken);
                $user->setIsVerified(false);

                $entityManager->persist($user);
                $entityManager->flush();

                $verificationUrl = $this->generateUrl(
                    'app_verify_email',
                    ['token' => $verificationToken],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                try {
                    $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
                } catch (\Throwable $e) {
                    error_log('[Registration] Verification email failed: '.$e->getMessage());
                }
                $request->getSession()->set('pending_verification_email', (string) $user->getEmail());
                $request->getSession()->set('pending_verification_sent_at', (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM));
                $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * Password must be at least 6 characters and contain only letters and numbers (no spaces or special characters).
     */
    private function isPasswordPolicySatisfied(?string $password): bool
    {
        if ($password === null) {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z0-9]{6,}$/', $password);
    }
}

