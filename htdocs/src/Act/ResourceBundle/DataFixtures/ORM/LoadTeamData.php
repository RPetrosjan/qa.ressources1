<?php

namespace Act\ResourceBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Act\ResourceBundle\Entity\Team;

/**
 * LoadTeamData
 *
 * Loads initial dataset of Teams
 *
 */
class LoadTeamData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $tech = new Team();
        $tech->setName('S. Tech');
        $tech->setColor('#1591f6');
        $manager->persist($tech);

        $crea = new Team();
        $crea->setName('S. Créa');
        $crea->setColor('#ff9995');
        $manager->persist($crea);

        $cpf = new Team();
        $cpf->setName('CP Fonctionnel');
        $cpf->setColor('#a2a593');
        $manager->persist($cpf);

        $manager->flush();

        // Add references to access these objets in other fixtures following this one
        $this->addReference('team-tech', $tech);
        $this->addReference('team-crea', $crea);
        $this->addReference('team-cpf', $cpf);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
