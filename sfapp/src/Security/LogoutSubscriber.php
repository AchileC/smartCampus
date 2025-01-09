<?php

namespace App\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // On s’inscrit à l’événement de déconnexion
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        // Récupérer la locale actuelle : soit par getLocale(), soit par la session.
        $locale = $request->getLocale();

        // Rediriger vers "/fr/rooms" ou "/en/rooms", selon la locale.
        $event->setResponse(new RedirectResponse(sprintf('/%s/rooms', $locale)));
    }
}
