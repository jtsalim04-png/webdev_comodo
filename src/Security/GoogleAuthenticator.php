<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class GoogleAuthenticator extends OAuth2Authenticator
{
    use TargetPathTrait;

    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return 'connect_google_check' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);
        $googleData = $googleUser->toArray();

        $email = $googleData['email'] ?? null;
        $name = $googleData['name'] ?? $email;

        if (!$email) {
            throw new AuthenticationException('No email from Google.');
        }

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($email, $name) {
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                if ($user instanceof User) {
                    // Existing accounts keep their role (no role re-selection).
                    // Still mark as verified to allow Google users to proceed.
                    $user->setIsVerified(true);
                    return $user;
                }

                $user = new User();
                $user->setEmail($email);

                $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
                $firstName = $parts[0] ?? 'Google';
                $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'User';

                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                // New Google sign-ups are organizers only.
                $user->setRole('ROLE_ORGANIZER');
                $user->setIsVerified(true);
                $user->setVerificationToken(null);
                $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        $roles = $user && method_exists($user, 'getRoles') ? $user->getRoles() : [];

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
        }

        if (in_array('ROLE_ORGANIZER', $roles, true)) {
            return new RedirectResponse($this->urlGenerator->generate('organizer_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('user_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->getFlashBag()->add('error', $exception->getMessageKey());
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
