<?php
// src/EventListener/LocaleListener.php
namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class LocaleListener
{
    private string $defaultLocale;
    private RequestStack $requestStack;

    public function __construct(string $defaultLocale = 'en', RequestStack $requestStack)
    {
        $this->defaultLocale = $defaultLocale;
        $this->requestStack = $requestStack;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        $session = $this->requestStack->getSession();

        if (!$session) {
            // Optionally handle cases where the session is not available
            $request->setLocale($this->defaultLocale);
            return;
        }

        // Retrieve or set the locale
        if ($locale = $request->attributes->get('_locale')) {
            $session->set('_locale', $locale);
        } else {
            $locale = $session->get('_locale', $this->defaultLocale);
            $request->setLocale($locale);
        }

        // Assurez-vous que la locale est initialisée
        if (!$request->attributes->get('_locale')) {
            $locale = $session->get('_locale', $this->defaultLocale);
            $request->attributes->set('_locale', $locale);
            $request->setLocale($locale);
        }
    }
}