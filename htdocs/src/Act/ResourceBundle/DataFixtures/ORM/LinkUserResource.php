<?php

namespace Act\ResourceBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Act\ResourceBundle\Entity\Resource;

/**
 * LinkUserResource
 *
 * Links some user to some resources
 *
 */
class LinkUserResource extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $resource = $this->getReference('resource-dev1');
        $resource->setUser($this->getReference('user-superadmin'));

        $resource2 = $this->getReference('resource-dev2');
        $resource2->setUser($this->getReference('user-user'));

        $resource3 = $this->getReference('resource-dev3');
        $resource3->setUser($this->getReference('user-rp'));

        $manager->persist($resource);
        $manager->persist($resource2);
        $manager->persist($resource3);

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 10;
    }
}
