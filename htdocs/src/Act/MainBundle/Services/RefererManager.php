<?php

namespace Act\MainBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RefererManager
{
    protected $request;
    protected $router;
    protected $defaultRoute;

    public function __construct(Request $request, RouterInterface $router, $defaultRoute)
    {
        $this->request = $request;
        $this->router = $router;
        $this->defaultRoute = $defaultRoute;
    }

    /**
     * Return the current referer
     * If no referer is found, use the default route and option given
     *
     * @param string $defaultRoute
     * @param array  $defaultOptions
     *
     * @return string
     */
    public function getReferer($defaultRoute = '', array $defaultOptions = array())
    {
        if(strlen($defaultRoute) == 0) $defaultRoute = $this->defaultRoute;

        // Try to get an explicit referer in the URL
        $referer = $this->request->query->get('referer');
        if ($referer == null) {
            // If no referer can be found, use the parameters given
            $referer = $this->router->generate($defaultRoute, $defaultOptions);
        }

        return $referer;
    }

    /**
     * Redirect user to the current referer
     * If no referer is found, use the default route and option given
     *
     * @param string $defaultRoute
     * @param array  $defaultOptions
     *
     * @return RedirectResponse
     */
    public function redirectReferer($defaultRoute = '', array $defaultOptions = array())
    {
        if(strlen($defaultRoute) == 0) $defaultRoute = $this->defaultRoute;

        return new RedirectResponse($this->getReferer($defaultRoute, $defaultOptions), 302);
    }
}
