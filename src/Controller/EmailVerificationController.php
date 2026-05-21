<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailVerificationController extends AbstractController
{
    #[Route('/verify-email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        EmailVerificationService $emailVerificationService
    ): Response {
        $token = $request->query->get('token');

        if (!$token) {
            $this->addFlash('error', 'Verification token is missing.');
            return $this->redirectToRoute('app_register');
        }

        $user = $emailVerificationService->verifyToken($token);

        if (!$user) {
            $this->addFlash('error', 'Invalid or expired verification token.');
            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Your email has been verified! You can now log in.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/resend-verification', name: 'app_resend_verification', methods: ['POST'])]
    public function resendVerificationEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        EmailVerificationService $emailVerificationService
    ): Response {
        if (!$this->isCsrfTokenValid('resend_verification', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid resend request.');
            return $this->redirectToRoute('app_login');
        }

        $email = (string) ($request->request->get('email') ?? $request->getSession()->get('pending_verification_email'));
        if ($email === '') {
            $this->addFlash('error', 'Missing email to resend verification.');
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $this->addFlash('error', 'No account found for that email.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $request->getSession()->remove('pending_verification_email');
            $request->getSession()->remove('pending_verification_sent_at');
            $this->addFlash('success', 'Your email is already verified. You can log in.');
            return $this->redirectToRoute('app_login');
        }

        $verificationToken = $emailVerificationService->generateVerificationToken();
        $user->setVerificationToken($verificationToken);
        $entityManager->flush();

        $verificationUrl = $this->generateUrl(
            'app_verify_email',
            ['token' => $verificationToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        $emailVerificationService->sendVerificationEmail($user, $verificationUrl);

        $request->getSession()->set('pending_verification_email', (string) $user->getEmail());
        $request->getSession()->set('pending_verification_sent_at', (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM));

        $this->addFlash('success', 'Verification email re-sent. Please check your inbox (and spam).');
        return $this->redirectToRoute('app_login');
    }
}
