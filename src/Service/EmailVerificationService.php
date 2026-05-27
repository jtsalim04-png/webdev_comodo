<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        #[Autowire('%env(default:default_mailer_from:MAILER_FROM_ADDRESS)%')]
        private string $mailerFromAddress,
        #[Autowire('%env(default:default_mailer_from_name:MAILER_FROM_NAME)%')]
        private string $mailerFromName,
    ) {
    }

    public function generateVerificationToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->mailerFromAddress, $this->mailerFromName))
            ->to(new Address((string) $user->getEmail()))
            ->subject('Please verify your email address')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }

    public function verifyToken(string $token): ?User
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $this->entityManager->flush();

        return $user;
    }
}
