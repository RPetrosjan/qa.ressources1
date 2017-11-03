<?php

namespace Act\ResourceBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Act\ResourceBundle\Entity\Resource;

/**
 * LoadResourceData
 *
 * Loads initial dataset of Resources
 *
 */
class LoadResourceData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadTechResources($manager);
        $this->loadCreaResources($manager);
        $this->loadCPFResources($manager);
    }

    private function loadTechResources(ObjectManager $manager)
    {
        $dev1 = new Resource();
        $dev1->setName('Développeur 1');
        $dev1->setNameShort('DEV1');
        $dev1->setStart(\DateTime::createFromFormat('j-m-Y', '01-03-2013'));
        $dev1->setLocation($this->getReference('location-strasbourg'));
        $dev1->setTeam($this->getReference('team-tech'));
        $manager->persist($dev1);

        $dev2 = new Resource();
        $dev2->setName('Développeur 2');
        $dev2->setNameShort('DEV2');
        $dev2->setStart(\DateTime::createFromFormat('j-m-Y', '15-06-2012'));
        $dev2->setEnd(\DateTime::createFromFormat('j-m-Y', '31-10-2013'));
        $dev2->setLocation($this->getReference('location-strasbourg'));
        $dev2->setTeam($this->getReference('team-tech'));
        $manager->persist($dev2);

        $dev3 = new Resource();
        $dev3->setName('Développeur 3');
        $dev3->setNameShort('DEV3');
        $dev3->setStart(\DateTime::createFromFormat('j-m-Y', '15-06-2012'));
        $dev3->setEnd(\DateTime::createFromFormat('j-m-Y', '10-12-2014'));
        $dev3->setLocation($this->getReference('location-paris'));
        $dev3->setTeam($this->getReference('team-tech'));
        $manager->persist($dev3);

        $dev4 = new Resource();
        $dev4->setName('Développeur 4');
        $dev4->setNameShort('DEV4');
        $dev4->setStart(\DateTime::createFromFormat('j-m-Y', '01-12-2013'));
        $dev4->setEnd(\DateTime::createFromFormat('j-m-Y', '01-02-2014'));
        $dev4->setLocation($this->getReference('location-paris'));
        $dev4->setTeam($this->getReference('team-tech'));
        $dev4->setDaysPerWeek(2.5);
        $manager->persist($dev4);

        $manager->flush();

        // Add references to access these objets in other fixtures following this one
        $this->addReference('resource-dev1', $dev1);
        $this->addReference('resource-dev2', $dev2);
        $this->addReference('resource-dev3', $dev3);
        $this->addReference('resource-dev4', $dev4);
    }

    private function loadCreaResources(ObjectManager $manager)
    {
        $crea1 = new Resource();
        $crea1->setName('Créa 1');
        $crea1->setNameShort('CREA1');
        $crea1->setStart(\DateTime::createFromFormat('j-m-Y', '01-01-2014'));
        $crea1->setLocation($this->getReference('location-strasbourg'));
        $crea1->setTeam($this->getReference('team-crea'));
        $manager->persist($crea1);

        $crea2 = new Resource();
        $crea2->setName('Créa 2');
        $crea2->setNameShort('CREA2');
        $crea2->setStart(\DateTime::createFromFormat('j-m-Y', '01-01-2014'));
        $crea2->setLocation($this->getReference('location-strasbourg'));
        $crea2->setTeam($this->getReference('team-crea'));
        $manager->persist($crea2);

        $crea3 = new Resource();
        $crea3->setName('Créa 3');
        $crea3->setNameShort('CREA3');
        $crea3->setStart(\DateTime::createFromFormat('j-m-Y', '01-01-2014'));
        $crea3->setLocation($this->getReference('location-paris'));
        $crea3->setTeam($this->getReference('team-crea'));
        $manager->persist($crea3);

        $manager->flush();

        // Add references to access these objets in other fixtures following this one
        $this->addReference('resource-crea1', $crea1);
        $this->addReference('resource-crea2', $crea2);
        $this->addReference('resource-crea3', $crea3);
    }

    private function loadCPFResources(ObjectManager $manager)
    {
        $cpf1 = new Resource();
        $cpf1->setName('Chef de projet fonctionnel 1');
        $cpf1->setNameShort('CPF1');
        $cpf1->setStart(\DateTime::createFromFormat('j-m-Y', '01-01-2014'));
        $cpf1->setLocation($this->getReference('location-strasbourg'));
        $cpf1->setTeam($this->getReference('team-cpf'));
        $manager->persist($cpf1);

        $cpf2 = new Resource();
        $cpf2->setName('Chef de projet fonctionnel 2');
        $cpf2->setNameShort('CPF2');
        $cpf2->setStart(\DateTime::createFromFormat('j-m-Y', '01-01-2014'));
        $cpf2->setLocation($this->getReference('location-strasbourg'));
        $cpf2->setTeam($this->getReference('team-cpf'));
        $manager->persist($cpf2);

        $manager->flush();

        // Add references to access these objets in other fixtures following this one
        $this->addReference('resource-cpf1', $cpf1);
        $this->addReference('resource-cpf2', $cpf2);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 3;
    }
}
