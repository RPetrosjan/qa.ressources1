<?php

namespace Act\LdapBundle\Security\Firewall;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Act\LdapBundle\Security\Authentication\Token\LdapToken;

/**
 * Class LdapListener
 *
 * This class is a listener to the firewall events.
 *
 * The role of this class is to get data from requests
 * and to try to create a token out of them.
 *
 * Then, this token must be authenticated from the authentication manager,
 * and set into the security context in case of successfull login.
 *
 */
class LdapListener implements ListenerInterface
{
    protected $securityContext;
    protected $authenticationManager;
    protected $session;
    protected $translator;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, SessionInterface $session, TranslatorInterface $translator)
    {
        $this->securityContext       = $securityContext;
        $this->authenticationManager = $authenticationManager;
        $this->session               = $session;
        $this->translator            = $translator;
    }

    /**
     * Firewall Listener handler
     *
     * Try to get the username & password from the request (from a form-login)
     * And then try to set an authenticated LdapToken into the security context
     *
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $username = $request->request->get('_username', null);
        $password = $request->request->get('_password', null);

        // If username and password are set in the request
        if ($username != null && $password != null) {

            // Create a new LdapToken to send to the authentication manager
            $token = new LdapToken($username, $password, 'ldap');

            try {
                // Try to authenticate the token, with a provider that supports LdapToken
                $authToken = $this->authenticationManager->authenticate($token);

                // Then set the authentified Token into the security context
                $this->securityContext->setToken($authToken);

            } catch (AuthenticationException $failed) {
                // Authentication failed
                // we must clear the token to show the login form again
                $this->securityContext->setToken(null);

                // Set the exception message in a flash message
                $this->session->getFlashBag()->add('error', $this->translator->trans($failed->getMessage()));

                throw $failed; // Throw exception to prevent login form from displaying errors
            }
        }
    }
}
