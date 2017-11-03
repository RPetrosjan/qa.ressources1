<?php

namespace Application\Sonata\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * LoadUserData
 *
 * Loads initial dataset of Users
 *
 */
class LoadUserData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $userManager = $this->container->get('fos_user.user_manager');

        // Create the ressources.admin user
        $user = $userManager->createUser();
        $user->setEnabled(true);
        $user->setUsername('ressources.admin');
        $user->setPlainPassword('Actency13*');
        $user->setEmail('ressources.admin@actency.fr');
        $userManager->updateUser($user);

        // Create the ressources.user user
        $user2 = $userManager->createUser();
        $user2->setEnabled(true);
        $user2->setUsername('ressources.user');
        $user2->setPlainPassword('Actency13*');
        $user2->setEmail('ressource.user@actency.fr');
        $userManager->updateUser($user2);

        //Create the ressources.rp user
        $user3 = $userManager->createUser();
        $user3->setEnabled(true);
        $user3->setUsername('ressources.rp');
        $user3->setPlainPassword('Actency13*');
        $user3->setEmail('ressources.rp@actency.fr');
        $user3->addRole('ROLE_RP');
        $userManager->updateUser($user3);

        // Add references to access these objects in other fixtures following this one
        $this->addReference('user-superadmin', $user);
        $this->addReference('user-user', $user2);
        $this->addReference('user-rp', $user3);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 8;
    }
}
