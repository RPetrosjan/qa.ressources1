<?php

namespace Act\MainBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Firewall\ExceptionListener as BaseExceptionListener;

/**
 * Change the default behavior for determining the target path
 * on a successful user login
 *
 * We need this because some bundles may redirect JS or JSON files...
 *
 * {@inheritDoc}
 */
class ExceptionListener extends BaseExceptionListener
{
    /**
     * {@inheritDoc}
     */
    protected function setTargetPath(Request $request)
    {
        if ($this->isRequestValidForRedirection($request)) {
            // After custom checking call parent
            parent::setTargetPath($request);

            // Make sure the redirect path is set for all of our firewalls
            $request->getSession()->set('_security.admin.target_path', $request->getUri());
            $request->getSession()->set('_security.main.target_path', $request->getUri());
        }
    }

    /**
     * Checks if the request is a valid one to be set as a redirection after login
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function isRequestValidForRedirection(Request $request)
    {
        if ($request->isXmlHttpRequest()                            // Don't keep AJAX requests
          || 'GET' !== $request->getMethod()                        // Don't keep not GET requests
          || $request->get('_route') == 'bazinga_jstranslation_js'  // Don't keep requests linked to bazinga bundle
          || $request->get('_route') == 'fos_js_routing_js'         // Don't keep requests linked to fos js routing bundle
        ) {
            return false;
        } else {
            return true;
        }
    }
}
