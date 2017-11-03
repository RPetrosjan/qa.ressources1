<?php

namespace Act\ResourceBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Translation\TranslatorInterface;

use Act\ResourceBundle\Services\User\FirstLogin;

/**
 * This class is here to check if the user has already registered a
 * linked resource to his account. Otherwise, we won't be able to use
 * the application, so we redirect him there as long as it is not done.
 */
class CheckLinkedUserListener
{
    protected $router;
    protected $translator;
    protected $firstLogin;

    /**
     * KernelRequest constructor
     *
     * @param Router     $router
     * @param Translator $translator
     * @param FirstLogin $firstLogin
     */
    public function __construct(RouterInterface $router, TranslatorInterface $translator, FirstLogin $firstLogin)
    {
        $this->router = $router;
        $this->translator = $translator;
        $this->firstLogin = $firstLogin;
    }

    /**
     * This method will be called before the controller is executed.
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $targetRoute = 'act_resource_user_first_login';
        $currentRoute = $event->getRequest()->attributes->get('_route');

        // Check if we are not on the right page already
        if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST && $currentRoute != $targetRoute) {
            // Check if we are not in the admin zone - because we want to allow the admin zone without a resource
            if ($currentRoute != 'sonata_admin_dashboard' && substr($currentRoute, 0, 6) != 'admin_') {
                if ($this->firstLogin->userNeedsResource()) {
                    // Display the page to choose an existing resource
                    $url = $this->router->generate($targetRoute);

                    $event->getRequest()->getSession()->getFlashBag()->add('warning', $this->translator->trans('choose.a.resource.before'));
                    $event->setResponse(new RedirectResponse($url, 302));
                }
            }
        }
    }
}
