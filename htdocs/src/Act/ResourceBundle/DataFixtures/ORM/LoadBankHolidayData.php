<?php

namespace Act\ResourceBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Act\ResourceBundle\Entity\BankHoliday;

/**
 * LoadBankholidayData
 *
 * Loads initial dataset of Bankholidays
 *
 */
class LoadBankHolidayData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $armistice = new BankHoliday();
        $armistice->setName('Armistice 1918');
        $armistice->setStart(\DateTime::createFromFormat('j-m-Y', '11-11-2013'));
        $armistice->addLocation($this->getReference('location-strasbourg'));
        $armistice->addLocation($this->getReference('location-paris'));
        $manager->persist($armistice);

        $toussaint = new BankHoliday();
        $toussaint->setName('Toussaint');
        $toussaint->setStart(\DateTime::createFromFormat('j-m-Y', '01-11-2013'));
        $toussaint->addLocation($this->getReference('location-strasbourg'));
        $toussaint->addLocation($this->getReference('location-paris'));
        $manager->persist($toussaint);

        $stetienne = new BankHoliday();
        $stetienne->setName('Saint Etienne');
        $stetienne->setStart(\DateTime::createFromFormat('j-m-Y', '26-12-2013'));
        $stetienne->addLocation($this->getReference('location-strasbourg'));
        $manager->persist($stetienne);

        $noel = new BankHoliday();
        $noel->setName('Noel');
        $noel->setStart(\DateTime::createFromFormat('j-m-Y', '25-12-2013'));
        $noel->addLocation($this->getReference('location-strasbourg'));
        $noel->addLocation($this->getReference('location-paris'));
        $manager->persist($noel);

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 4;
    }
}
