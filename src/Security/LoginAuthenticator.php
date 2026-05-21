<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public function authenticate(Request $request): Passport
    {
        $email = (string) ($request->request->get('email') ?? $request->getPayload()->getString('email'));
        $password = (string) ($request->request->get('password') ?? $request->getPayload()->getString('password'));
        $csrfToken = (string) ($request->request->get('_csrf_token') ?? $request->getPayload()->getString('_csrf_token'));

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Get user roles first
        $user = $token->getUser();
        $roles = $user && method_exists($user, 'getRoles') ? $user->getRoles() : [];
        
        $session = $request->getSession();
        $targetPathKey = '_security.' . $firewallName . '.target_path';
        
        // Always clear any target path first to prevent issues
        $targetPath = $this->getTargetPath($session, $firewallName);
        
        // Clear target path from session immediately
        $session->remove($targetPathKey);
        
        // Check if target path is safe for this user's role
        if ($targetPath) {
            $targetPathLower = strtolower($targetPath);
            $isAdminRoute = strpos($targetPathLower, '/admin') !== false;
            $isOrganizerRoute = strpos($targetPathLower, '/organizer') !== false;
            
            if (in_array('ROLE_ADMIN', $roles, true)) {
                // Admin can go anywhere
                return new RedirectResponse($targetPath);
            } elseif (in_array('ROLE_ORGANIZER', $roles, true)) {
                // Organizer can go to organizer routes or home (but not admin routes)
                if (!$isAdminRoute) {
                    return new RedirectResponse($targetPath);
                }
            } else {
                // Regular users: only allow safe routes (not admin/organizer)
                if (!$isAdminRoute && !$isOrganizerRoute) {
                    return new RedirectResponse($targetPath);
                }
                // If target path is unsafe, fall through to role-based redirect
            }
        }

        // Redirect based on user role (no target path or invalid target path was cleared)
        if ($user) {
            if (in_array('ROLE_ADMIN', $roles, true)) {
                return new RedirectResponse($this->urlGenerator->generate('admin_dashboard'));
            }
            if (in_array('ROLE_ORGANIZER', $roles, true)) {
                return new RedirectResponse($this->urlGenerator->generate('organizer_dashboard'));
            }
        }

        // Default redirect for regular users - go to their dashboard
        return new RedirectResponse($this->urlGenerator->generate('user_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}

