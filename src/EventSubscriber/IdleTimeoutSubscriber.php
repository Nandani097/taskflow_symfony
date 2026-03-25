<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class IdleTimeoutSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private UrlGeneratorInterface $urlGenerator;
    private LoggerInterface $logger;
    
    // Changed to 60 seconds (1 minute) for normal testing
    private int $maxIdleTime = 60;

    public function __construct(Security $security, UrlGeneratorInterface $urlGenerator, LoggerInterface $logger)
    {
        $this->security = $security;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // NEW FEATURE: Check if the user has the 'Remember Me' cookie!
        // If they checked the box during login, they want to stay logged in, so we bypass the timeout completely!
        if ($request->cookies->has('REMEMBERME')) {
            $this->logger->info('IdleTimeout: User has a Remember Me cookie. Skipping timeout check!');
            return;
        }

        if (!$request->hasPreviousSession()) {
            $this->logger->info('IdleTimeout: No previous session found.');
            return;
        }

        $session = $request->getSession();
        $session->start();
        
        $user = $this->security->getUser();
        if (!$user) {
            $this->logger->info('IdleTimeout: User is not logged in.');
            return;
        }

        $lastUsed = $session->getMetadataBag()->getLastUsed();
        $currentTime = time();
        $idleTime = $currentTime - $lastUsed;

        $this->logger->info(sprintf(
            'IdleTimeout: User %s is logged in. Last used: %d. Current time: %d. Idle for: %d seconds. Max allowed: %d.',
            $user->getUserIdentifier(),
            $lastUsed,
            $currentTime,
            $idleTime,
            $this->maxIdleTime
        ));

        if ($idleTime > $this->maxIdleTime) {
            $this->logger->warning('IdleTimeout: LOGGING OUT USER due to inactivity! Clearing remember-me cookie too.');
            
            // SECURITY FIX: Invalidate session ALONE is not enough because the "Remember Me"
            // cookie will instantly log them back in on the next request!
            // We use the modern Symfony Security component to fully logout and clear cookies.
            $logoutResponse = $this->security->logout(false);
            
            // Add our warning flash message to the brand new anonymous session
            $request->getSession()->getFlashBag()->add('warning', 'Logged out due to inactivity for your security!');
            
            if ($logoutResponse) {
                $event->setResponse($logoutResponse);
            } else {
                $loginUrl = $this->urlGenerator->generate('app_login');
                $event->setResponse(new RedirectResponse($loginUrl));
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Run after the firewall (Priority ~8) so the User token is loaded
            KernelEvents::REQUEST => ['onKernelRequest', -10],
        ];
    }
}
