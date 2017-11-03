<?php

namespace Act\ResourceBundle\Listener;

use FOS\UserBundle\Model\UserManagerInterface;
use Act\LdapBundle\Event\LdapUserLoadedEvent;

class LdapUserLoadedListener
{
    protected $userManager;

    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * LdapUserLoadedEvent listener callback
     * After the user is loaded from the user provider,
     * we must refresh its password in case it changed on
     * the LDAP server.
     *
     * @param  LdapUserLoadedEvent $event
     * @return LdapUserLoadedEvent
     */
    public function onUserLoaded(LdapUserLoadedEvent $event)
    {
        $info = $event->getInfo();
        $user = $event->getUser();

        // Refresh the password
        $user->setPlainPassword($event->getPassword());

        // Refresh the email
        if (isset($info[0]['mail'][0])) {
            $user->setEmail($info[0]['mail'][0]);
        }

        $this->userManager->updateUser($user);
        $event->setUser($user);

        return $event;
    }
}
