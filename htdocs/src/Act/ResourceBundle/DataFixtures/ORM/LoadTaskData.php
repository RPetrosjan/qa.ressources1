<?php

namespace Act\ResourceBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * LoadProjectData
 *
 * Loads initial dataset of Projects
 *
 */
class LoadTaskData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $meta = new \Act\ResourceBundle\Entity\MetaTask();
        $meta->setName('Metatâche 1');
        $meta->setStart(\DateTime::createFromFormat('j-m-Y', '25-11-2013'));
        $meta->setEnd(\DateTime::createFromFormat('j-m-Y', '25-12-2013'));
        $meta->setWorkloadSold(12);
        $meta->setProject($this->getReference('project-1'));
        $manager->persist($meta);

        $common1 = new \Act\ResourceBundle\Entity\CommonTask();
        $common1->setName('Tâche 1');
        $common1->setStart(\DateTime::createFromFormat('j-m-Y', '02-12-2013'));
        $common1->setEnd(\DateTime::createFromFormat('j-m-Y', '06-12-2013'));
        $common1->setWorkloadSold(5);
        $common1->setProject($this->getReference('project-1'));
        $common1->setMetatask($meta);
        $manager->persist($common1);

        $common2 = new \Act\ResourceBundle\Entity\CommonTask();
        $common2->setName('Tâche 2');
        $common2->setStart(\DateTime::createFromFormat('j-m-Y', '09-12-2013'));
        $common2->setEnd(\DateTime::createFromFormat('j-m-Y', '13-12-2013'));
        $common2->setWorkloadSold(7);
        $common2->setProject($this->getReference('project-1'));
        $common2->setMetatask($meta);
        $manager->persist($common2);

        $sub1 = new \Act\ResourceBundle\Entity\SubTask();
        $sub1->setName('Sous-tâche 1');
        $sub1->setStart(\DateTime::createFromFormat('j-m-Y', '02-12-2013'));
        $sub1->setEnd(\DateTime::createFromFormat('j-m-Y', '04-12-2013'));
        $sub1->setWorkloadSold(3);
        $sub1->setProject($this->getReference('project-1'));
        $sub1->setCommontask($common1);
        $manager->persist($sub1);

        $sub2 = new \Act\ResourceBundle\Entity\SubTask();
        $sub2->setName('Sous-tâche 2');
        $sub2->setStart(\DateTime::createFromFormat('j-m-Y', '05-12-2013'));
        $sub2->setEnd(\DateTime::createFromFormat('j-m-Y', '06-12-2013'));
        $sub2->setWorkloadSold(2);
        $sub2->setProject($this->getReference('project-1'));
        $sub2->setCommontask($common1);
        $manager->persist($sub2);

        $sub3 = new \Act\ResourceBundle\Entity\SubTask();
        $sub3->setName('Sous-tâche 3');
        $sub3->setStart(\DateTime::createFromFormat('j-m-Y', '09-12-2013'));
        $sub3->setEnd(\DateTime::createFromFormat('j-m-Y', '12-12-2013'));
        $sub3->setWorkloadSold(4);
        $sub3->setProject($this->getReference('project-1'));
        $sub3->setCommontask($common2);
        $manager->persist($sub3);

        $sub4 = new \Act\ResourceBundle\Entity\SubTask();
        $sub4->setName('Sous-tâche 4');
        $sub4->setStart(\DateTime::createFromFormat('j-m-Y', '11-12-2013'));
        $sub4->setEnd(\DateTime::createFromFormat('j-m-Y', '13-12-2013'));
        $sub4->setWorkloadSold(3);
        $sub4->setProject($this->getReference('project-1'));
        $sub4->setCommontask($common2);
        $manager->persist($sub4);

        $manager->flush();

        // Add references to access these objets in other fixtures following this one
        $this->addReference('meta-1', $meta);
        $this->addReference('common-1', $common1);
        $this->addReference('common-2', $common2);
        $this->addReference('sub-1', $sub1);
        $this->addReference('sub-2', $sub2);
        $this->addReference('sub-3', $sub3);
        $this->addReference('sub-4', $sub4);
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 6;
    }
}
