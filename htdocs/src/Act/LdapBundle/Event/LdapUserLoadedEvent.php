<?php

namespace Act\LdapBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class LdapUserLoadedEvent
 *
 * The event is thrown when a user was loaded from the
 * user provider but before it is returned in the
 * authenticated token.
 *
 */
class LdapUserLoadedEvent extends Event
{
    // Token credentials, used to test against the LDAP
    protected $username;
    protected $password;

    // The user retrieved by user provider
    protected $user;
    protected $error;

    // Ldap information - only to read
    protected $info;

    public function __construct($username, $password, UserInterface $user, array $info)
    {
        $this->username = $username;
        $this->password = $password;
        $this->user     = $user;
        $this->error    = '';
        $this->info     = $info;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getUser()
    {
        return $this->user;
    }

    /**
     * Allow to set null User to prevent from logging in
     *
     * @param UserInterface $user
     */
    public function setUser(UserInterface $user = null)
    {
        $this->user = $user;
    }

    public function setError($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getInfo()
    {
        return $this->info;
    }
}
