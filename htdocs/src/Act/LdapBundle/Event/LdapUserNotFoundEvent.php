<?php

namespace Act\LdapBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class LdapUserNotFoundEvent
 *
 * The event is thrown when a user can't be
 * retrieved by the user provider.
 *
 * If any listener set a properly initialized
 * user object into the event, the login will be successfull.
 *
 */
class LdapUserNotFoundEvent extends Event
{
    // Token credentials, used to test against the LDAP
    protected $username;
    protected $password;

    // The user that must be initialized by at least one event listener
    protected $user;

    // Ldap information - only to read
    protected $info;

    public function __construct($username, $password, array $info)
    {
        $this->username = $username;
        $this->password = $password;
        $this->user     = null;
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

    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }

    public function getInfo()
    {
        return $this->info;
    }
}
