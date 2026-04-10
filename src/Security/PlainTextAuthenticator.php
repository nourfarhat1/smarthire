<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class PlainTextAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UserRepository $userRepository,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        // Manually set the last username string to avoid Undefined Constant error
        $request->getSession()->set('_security.last_username', $email);

        return new SelfValidatingPassport(
            new UserBadge($email, function ($userIdentifier) use ($password) {
                $user = $this->userRepository->findOneByEmail($userIdentifier);
                
                if (!$user) {
                    throw new UserNotFoundException('Email could not be found.');
                }
                
                // Ported JavaFX logic check
                if (method_exists($user, 'isBanned') && $user->isBanned()) {
                    throw new AuthenticationException('Your account has been suspended.');
                }
                
                // Compare plain text passwords
                if ($user->getPassword() !== $password) {
                    throw new AuthenticationException('Invalid credentials.');
                }
                
                return $user;
            }),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        
        // Role-based redirection logic matching your HR System needs
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
                return new RedirectResponse($targetPath);
            }
            return new RedirectResponse($this->urlGenerator->generate('app_admin'));
        } 
        
        if (in_array('ROLE_HR', $user->getRoles())) {
            return new RedirectResponse($this->urlGenerator->generate('app_hr_dashboard'));
        }

        if (in_array('ROLE_CANDIDATE', $user->getRoles())) {
            return new RedirectResponse($this->urlGenerator->generate('app_candidate_dashboard'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
