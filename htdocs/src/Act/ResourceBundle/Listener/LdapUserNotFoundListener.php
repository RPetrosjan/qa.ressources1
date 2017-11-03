<?php

namespace Act\ResourceBundle\Listener;

use FOS\UserBundle\Model\GroupManagerInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Act\LdapBundle\Event\LdapUserNotFoundEvent;

class LdapUserNotFoundListener
{
    protected $userManager;
    protected $groupManager;

    public function __construct(UserManagerInterface $userManager, GroupManagerInterface $groupManager)
    {
        $this->userManager  = $userManager;
        $this->groupManager = $groupManager;
    }

    /**
     * LdapUserNotFoundEvent listener callback
     * When the ldap bind is successful, but the user is not found in the database,
     * we must create a new user and persist it using the FOSUserBundle user manager.
     *
     * @param  LdapUserNotFoundEvent $event
     * @return LdapUserNotFoundEvent
     */
    public function onUserNotFound(LdapUserNotFoundEvent $event)
    {
        // Fake an email if not found in the Ldap info
        $mail = $event->getUsername().'@actency.fr';
        $info = $event->getInfo();
        if (isset($info[0]['mail'][0])) {
            $mail = $info[0]['mail'][0];
        }

        $user = $this->userManager->createUser();
        $user->setUsername($event->getUsername());
        $user->setPlainPassword($event->getPassword());
        $user->setEmail($mail);
        $user->setEnabled(true);
        $this->userManager->updateUser($user);

        // Find group, create if it does not exist
        $groupName = 'Utilisateurs';
        $group = $this->groupManager->findGroupByName($groupName);
        if (!$group) {
            $group = $this->groupManager->createGroup($groupName);
            $group->addRole('ROLE_USER');
            $this->groupManager->updateGroup($group);
        }

        // Add the user to the group
        $user->addGroup($group);

        // Put our newly created user into the event
        $event->setUser($user);

        return $event;
    }
}
