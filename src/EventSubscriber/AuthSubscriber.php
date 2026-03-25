<?php

namespace App\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuthSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $authLogger
    ) {}

    public static function getSubscribedEvents(): array
    {
        // Tell Symfony: "When these security events happen, run my functions!"
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        // The Bouncer hands us the Event object. We can ask the Event who the user is!
        $user = $event->getUser();
        $userIdentifier = $user ? $user->getUserIdentifier() : 'unknown';
        
        // [CUSTOM LOGGING] Write to var/log/auth.log
        $this->authLogger->info('Successful login.', [
            'user' => $userIdentifier
        ]);
    }

    public function onLogout(LogoutEvent $event): void
    {
        // When logging out, we have to look inside the security "Token" to see who it was.
        $token = $event->getToken();
        $userIdentifier = 'unknown';

        if ($token && $user = $token->getUser()) {
            if (method_exists($user, 'getUserIdentifier')) {
                $userIdentifier = $user->getUserIdentifier();
            }
        }
        
        // [CUSTOM LOGGING] Write to var/log/auth.log
        $this->authLogger->info('Successful logout.', [
            'user' => $userIdentifier
        ]);
    }
}
