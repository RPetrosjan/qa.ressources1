<?php

namespace Act\LdapBundle\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class LdapToken
 *
 * This token contains data to authenticate a user via a LDAP server.
 *
 * So it extends from the UsernamePasswordToken because we just need
 * these credentials to try to bind the server.
 *
 */
class LdapToken extends UsernamePasswordToken
{
    public function __construct($user, $credentials, $providerKey, array $roles = array())
    {
        parent::__construct($user, $credentials, $providerKey, $roles);
    }
}
