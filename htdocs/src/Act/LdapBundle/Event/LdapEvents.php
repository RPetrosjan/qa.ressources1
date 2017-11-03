<?php

namespace Act\LdapBundle\Event;

/**
 * Class LdapEvents
 *
 * Describes the events thrown by this bundle
 *
 */
final class LdapEvents
{
    /**
     * The ldap.user.not.found event is thrown when a user credentials
     * are validated with the ldap bind operation, but when this user can't
     * be retrieved by the used user provider.
     *
     * The event listener receives an Act\LdapBundle\Event\LdapUserNotFoundEvent instance.
     *
     * @var string
     */
    const LDAP_USER_NOT_FOUND_EVENT = 'ldap.user.not.found';

    /**
     * The ldap.user.loaded event is thrown when a user was just retrieved
     * by the used user provider.
     *
     * The event listener receives an Act\LdapBundle\Event\LdapUserLoadedEvent instance.
     *
     * @var string
     */
    const LDAP_USER_LOADED_EVENT = 'ldap.user.loaded';
}
