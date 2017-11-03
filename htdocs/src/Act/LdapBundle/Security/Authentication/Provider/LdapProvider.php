<?php

namespace Act\LdapBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

use Act\LdapBundle\Security\Authentication\Token\LdapToken;
use Act\LdapBundle\Event\LdapUserNotFoundEvent;
use Act\LdapBundle\Event\LdapUserLoadedEvent;
use Act\LdapBundle\Event\LdapEvents;

/**
 * Class LdapProvider
 *
 * The authentication provider must try to authenticate a Token.
 *
 * In our case, it will authenticate the LdapToken, if it can
 * successfully bind to a given LDAP server.
 *
 * If the user can't be retrieved from the user provider,
 * an event is dispatched to let other bundles try to
 * set an initialized user before sending an error.
 *
 */
class LdapProvider implements AuthenticationProviderInterface
{
    private $dispatcher;
    private $userProvider;
    private $server;
    private $port;
    private $prefix;
    private $baseDN;
    private $userField;

    public function __construct(EventDispatcherInterface $dispatcher, UserProviderInterface $userProvider, $server, $port, $prefix = null, $baseDN, $userField)
    {
        $this->dispatcher   = $dispatcher;
        $this->userProvider = $userProvider;
        $this->server       = $server;
        $this->port         = $port;
        $this->prefix       = (is_null($prefix) ? '' : $prefix);
        $this->baseDN       = $baseDN;
        $this->userField    = $userField;
    }

    /**
     * Try to authenticate the LdapToken
     *
     * @param  TokenInterface                                                     $token
     * @return TokenInterface
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    public function authenticate(TokenInterface $token)
    {
        // Try to connect to Ldap server
        if (!$conn = ldap_connect($this->server, $this->port)) {
            $this->throwException('ldap.connect.failed', $token);
        }

        // Try to authenticate against Ldap server
        if (!@ldap_bind($conn, $this->prefix.$token->getUsername(), $token->getCredentials())) {
            $this->throwException('ldap.bind.failed', $token);
        }

        // Try to load information from the Ldap
        $info = array();
        if (strlen($this->userField) > 0 && strlen($this->baseDN) > 0) {
            $search = ldap_search($conn, $this->baseDN, $this->userField . '=' . $token->getUsername());
            if ($search != false) {
                $info = ldap_get_entries($conn, $search);
            }
        }

        // Authentication success : we can disconnect from the ldap server
        ldap_unbind($conn);

        // Now we need to check if the user already exists in the database
        try {
            $user = $this->userProvider->loadUserByUsername($token->getUsername());
        } catch (UsernameNotFoundException $e) {
            // The user doesn't already exists
            // We dispatch an event to let developers set custom behaviour
            // for getting back or creating an user to use
            $event = new LdapUserNotFoundEvent($token->getUsername(), $token->getCredentials(), $info);
            $this->dispatcher->dispatch(LdapEvents::LDAP_USER_NOT_FOUND_EVENT, $event);

            if ($event->getUser() == null) {
                // If the user was not initialized by any listener/subscriber
                // we can't do anything but throw the exception
                throw $e;
            } else {
                $user = $event->getUser();
            }
        }

        // After that the user was loaded from the user provider, we throw an other event
        $event = new LdapUserLoadedEvent($token->getUsername(), $token->getCredentials(), $user, $info);
        $this->dispatcher->dispatch(LdapEvents::LDAP_USER_LOADED_EVENT, $event);

        // Get the user after the event dispatch, that may be altered
        $user = $event->getUser();
        if ($user == null) {
            // If no user found, throw exception
            $this->throwException($event->getError(), $token);
        }

        // Execute some more security verifications
        $this->securityChecks($user, $token);

        // Return the token which will be now properly authenticated
        return new LdapToken($user->getUsername(), $user->getPassword(), 'ldap', array('ROLE_LDAP_LOGGED'));
    }

    /**
     * Some required security checks for advanced users
     *
     * @param $user
     * @param $token
     */
    private function securityChecks($user, $token)
    {
        if ($user instanceof AdvancedUserInterface) {
            if (!$user->isEnabled()) {
                // If the account is enabled, expired or locked, don't allow to log in
                $this->throwException('user.account.disabled', $token);
            }

            if ($user->isExpired()) {
                // If the account is enabled, expired or locked, don't allow to log in
                $this->throwException('user.account.expired', $token);
            }

            if ($user->isLocked()) {
                // If the account is enabled, expired or locked, don't allow to log in
                $this->throwException('user.account.locked', $token);
            }
        }
    }

    /**
     * Helper to throw an exception
     *
     * @param $message
     * @param $token
     *
     * @throws \Symfony\Component\Security\Core\Exception\AuthenticationException
     */
    private function throwException($message, $token)
    {
        $exception = new AuthenticationException($message);
        $exception->setToken($token);
        throw $exception;
    }

    /**
     * This provider only supports LdapToken
     *
     * @param  TokenInterface $token
     * @return bool
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof LdapToken;
    }
}
