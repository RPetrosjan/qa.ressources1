<?php

namespace Act\ResourceBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Act\ResourceBundle\Entity\Project;

/**
 * LoadProjectData
 *
 * Loads initial dataset of Projects
 *
 */
class LoadProjectData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $conges = new Project();
        $conges->setName('Congés');
        $conges->setNameShort('CONGES');
        $conges->setColor('#C0C0C0');
        $conges->setTypeHoliday(true);
        $conges->setStart(new \DateTime('2013-12-06 00:00:00'));
        $conges->setEnd(new \DateTime('2014-12-06 00:00:00'));
        $manager->persist($conges);

        $proj1 = new Project();
        $proj1->setName('Projet 1');
        $proj1->setNameShort('PR1');
        $proj1->setStart(new \DateTime('2013-12-06 00:00:00'));
        $proj1->setEnd(new \DateTime('2014-12-06 00:00:00'));
        $manager->persist($proj1);

        $proj2 = new Project();
        $proj2->setName('Projet 2');
        $proj2->setNameShort('PR2');
        $proj2->setStart(new \DateTime('2013-12-06 00:00:00'));
        $proj2->setEnd(new \DateTime('2014-12-06 00:00:00'));
        $manager->persist($proj2);

        // Add a disabled project
        $proj3 = new Project();
        $proj3->setName('Ancien projet');
        $proj3->setNameShort('OLD');
        $proj3->setActive(false);
        $proj3->setStart(new \DateTime('2013-12-06 00:00:00'));
        $proj3->setEnd(new \DateTime('2014-12-06 00:00:00'));
        $manager->persist($proj3);

        $manager->flush();

        // Add references to access these objets in other fixtures following this one
        $this->addReference('project-conges', $conges);
        $this->addReference('project-1', $proj1);
        $this->addReference('project-2', $proj2);
        $this->addReference('project-disabled', $proj3);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 5;
    }
}
