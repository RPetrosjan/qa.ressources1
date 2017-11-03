<?php
/**
 * Created by PhpStorm.
 * User: ljeannelle
 * Date: 06/05/14
 * Time: 16:59
 */

namespace Act\ResourceBundle\DataFixtures\ORM;

use Act\ResourceBundle\Entity\PreferedProject;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadPreferedProjectData extends AbstractFixture implements  OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $pref = new PreferedProject();
        $pref->setProject($this->getReference('project-1'));
        $pref->setUser($this->getReference('user-superadmin'));

        $manager->persist($pref);

        $manager->flush();

        $this->addReference('prefered-project1', $pref);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 10;
    }

}
