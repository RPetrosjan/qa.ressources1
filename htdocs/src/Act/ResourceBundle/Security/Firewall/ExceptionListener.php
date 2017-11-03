<?php

namespace Act\ResourceBundle\Security\Firewall;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Firewall\ExceptionListener as BaseExceptionListener;

/**
 * Change the default behavior for determining the target path
 * When the user log in
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
        // Don't keep Ajax requests or non-GET requests for target path
        if ($request->isXmlHttpRequest() || 'GET' !== $request->getMethod() || $request->get('_route') == 'bazinga_jstranslation_js') {
            return;
        }

        $request->getSession()->set('_security.target_path', $request->getUri());
    }
}
