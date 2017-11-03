<?php

namespace Application\Sonata\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * LoadGroupData
 *
 * Loads initial dataset of Groups
 *
 */
class LoadGroupData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $groupManager = $this->container->get('fos_user.group_manager');

        // Create the super administrator group
        $superAdminGroup = $groupManager->createGroup('Super Administrateurs');
        $superAdminGroup->addRole('ROLE_SUPER_ADMIN');
        $groupManager->updateGroup($superAdminGroup);

        // Create the administrator group
        $adminGroup = $groupManager->createGroup('Administrateurs');
        $adminGroup->addRole('ROLE_ADMIN');
        $groupManager->updateGroup($adminGroup);

        // Create the users group
        $userGroup = $groupManager->createGroup('Utilisateurs');
        $userGroup->addRole('ROLE_USER');
        $groupManager->updateGroup($userGroup);

        // Add the superadmin user to the superadmin group
        $admin = $this->getReference('user-superadmin');
        $admin->addGroup($superAdminGroup);

        // Add the user to the user group
        $user = $this->getReference('user-user');
        $user->addGroup($userGroup);

        $manager->persist($admin);
        $manager->persist($user);
        $manager->flush();

        // Add references to access these objects in other fixtures following this one
        $this->addReference('group-superadmin', $superAdminGroup);
        $this->addReference('group-admin', $adminGroup);
        $this->addReference('group-user', $userGroup);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 9;
    }
}
