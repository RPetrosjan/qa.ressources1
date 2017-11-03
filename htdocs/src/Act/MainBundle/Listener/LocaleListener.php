<?php

namespace Act\MainBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class LocaleListener
 *
 * Custom listener to ensure that the right locale
 * is set for each request.
 *
 * @package Act\MainBundle
 */
class LocaleListener
{
    private $defaultLocale;

    public function __construct($defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }
    }
}
