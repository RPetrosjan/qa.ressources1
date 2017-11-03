<?php

namespace Act\ResourceBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Act\ResourceBundle\Entity\Location;

/**
 * LoadLocationData
 *
 * Loads initial dataset of Locations
 *
 */
class LoadLocationData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $strasbourg = new Location();
        $strasbourg->setName('Strasbourg');
        $manager->persist($strasbourg);

        $paris = new Location();
        $paris->setName('Paris');
        $manager->persist($paris);

        $manager->flush();

        // Add references to access these objets in other fixtures following this one
        $this->addReference('location-strasbourg', $strasbourg);
        $this->addReference('location-paris', $paris);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1; // This fixture will be the first to be executed
    }
}
