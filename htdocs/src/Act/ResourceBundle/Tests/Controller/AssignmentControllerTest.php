<?php

namespace Act\ResourceBundle\Tests\Controller;

use Act\MainBundle\Tests\IsolatedTestCase;
use Act\ResourceBundle\Entity\Assignment;
use Act\ResourceBundle\Entity\BankHoliday;

/**
 * Class AssignmentControllerTest
 *
 * Contains all tests relative to the assignment controller
 */
class AssignmentControllerTest extends IsolatedTestCase
{
    /**
     * Tests relative to the previsional assignments dashboard
     */
    public function testPrevisional()
    {
        // Adding a bankholiday on last day of the week
        $bankholiday = new BankHoliday();
        $bankholiday->setName('Test bankholiday');
        $bankholiday->setStart(new \DateTime('2013-12-06 00:00:00', new \DateTimeZone('Europe/Paris')));
        $bankholiday->addLocation($this->em->getRepository('ActResourceBundle:Location')->findOneBy(array('name' => 'Paris')));
        $this->em->persist($bankholiday);
        $this->em->flush();

        // Generate the route to the previsional assignments page
        $route = $this->client->getContainer()->get('router')->generate('act_resource_assignment_previsional');

        // Get the yearly summary with some parameters
        $crawler = $this->client->request('GET', $route, array(
            'week' => 49,
            'year' => 2013
        ));

        /**
         * Check if the response if OK
         */
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        /**
         * Check the assignments for DEV1
         */
        $workloads = array(
            array(
                'project' => 'Projet 1',
                'workload' => '1h45'
            ),
            array(
                'project' => 'Projet 1',
                'workload' => '3h30'
            ),
            array(
                'project' => 'Projet 1',
                'workload' => '5h15'
            ),
            array(
                'project' => 'Projet 1',
                'workload' => '7h'
            ),
            array(
                'project' => 'Projet 1',
                'workload' => '10h30'
            )
        );

        $thisClosure = $this;
        $crawler->filter('table tbody tr.ressrow')->reduce(function ($node, $i) {
            // Get only the 1st row - the DEV1 assignments
            return($i == 0);
        })->filter('td')->reduce(function ($node, $i) {
            return ($i > 0);
        })->filter('div.inner')->each(function ($node, $i) use ($thisClosure, $workloads) {
            $workload = trim($node->filter('span.workload')->html());
            $project = trim($node->filter('a.project-name')->html());

            $thisClosure->assertEquals($workloads[$i]['project'], $project);
            $thisClosure->assertEquals($workloads[$i]['workload'], $workload);
        });

        /**
         * Check the assignments for DEV3
         */
        $workloads = array(
            array(
                'workloads' => array('1h30', '7h'),
                'projects' => array('Projet 1', 'Projet 2')
            ),
            array(
                'projects' => array('Projet 2', 'Projet 1'),
                'workloads' => array('1h45', '1h30')
            ),
            array(
                'projects' => array('Projet 1', 'Projet 2'),
                'workloads' => array('3h30', '3h30')
            ),
            array(
                'projects' => array('Projet 1'),
                'workloads' => array('7h')
            ),
            array()
        );

        $thisClosure = $this;
        $crawler->filter('table tbody tr.ressrow')->reduce(function ($node, $i) {
            // Get only the 3rd row - the DEV3 assignments
            return($i == 2);
        })->filter('td')->reduce(function ($node, $i) {
            return ($i > 0);
        })->each(function ($node, $i) use ($thisClosure, $workloads) {
            $data = $workloads[$i];
            $workloads = array();
            $projects = array();

            $node->filter('span.workload')->each(function ($node, $i) use (&$workloads) {
                $workloads[] = trim($node->html());
            });

            $node->filter('a.project-name')->each(function ($node, $i) use (&$projects) {
                $projects[] = trim($node->html());
            });

            foreach ($workloads as $workload) {
                $thisClosure->assertContains($workload, $data['workloads']);
            }

            foreach ($projects as $project) {
                $thisClosure->assertContains($project, $data['projects']);
            }
        });

        // Check for the bankholiday for DEV3 on last day
        $crawler->filter('table tbody tr.ressrow')->reduce(function ($node, $i) {
          // Get only the 3rd row - the DEV3 assignments
          return($i == 2);
        })->filter('td')->reduce(function ($node, $i) {
            return ($i == 5);
        })->each(function ($node, $i) use ($thisClosure, $workloads) {
            $html = trim($node->html());

            $thisClosure->assertEquals('Test bankholiday', $html);
        });
    }

    /**
     * Tests relative to the previsional assignments dashboard navigation
     * Next, Previous and Reset buttons
     */
    public function testPrevisionalNavigation()
    {
        // Generate the route to the previsional assignments page
        $route = $this->client->getContainer()->get('router')->generate('act_resource_assignment_previsional');

        // Get the yearly summary with some parameters
        $crawler = $this->client->request('GET', $route, array(
            'week' => 49,
            'year' => 2013,
            'teams' => array(1, 2)
        ));

        /**
         * Click the next button and check the week and year
         * many times. And then, click the previous button and
         * check also that the week is correct.
         */
        $weeks = array('W50', 'W51', 'W52', 'W01', 'W02');
        for ($i = 0; $i < count($weeks); $i++) {
            $nextLink = $crawler->filter('a.next-btn')->link();

            $crawler = $this->client->click($nextLink);
            $this->assertEquals($weeks[$i], trim($crawler->filter('#page-title h1 span.week')->html()));
        }

        $weeks = array('W01', 'W52', 'W51', 'W50', 'W49');
        for ($i = 0; $i < count($weeks); $i++) {
            $prevLink = $crawler->filter('a.previous-btn')->link();
            $crawler = $this->client->click($prevLink);
            $this->assertEquals($weeks[$i], trim($crawler->filter('#page-title h1 span.week')->html()));
        }

        /**
         * Press the reset button and check that we come back to the current week
         */
        $resetLink = $crawler->filter('a.reset-btn')->link();
        $crawler = $this->client->click($resetLink);
        $now = new \DateTime("now");
        $this->assertEquals('W'.$now->format('W'), trim($crawler->filter('#page-title h1 span.week')->html()));
    }

    /**
     * Tests relative to the previsional assignments dashboard filters
     * - show only resources affected to my projects
     * - show only resources affected to selected projects
     */
    public function testPrevisionalFilters()
    {
        // Generate the route to the previsional assignments page
        $route = $this->client->getContainer()->get('router')->generate('act_resource_assignment_previsional');

        // Get the yearly summary with some parameters
        $crawler = $this->client->request('GET', $route, array(
            'week' => 49,
            'year' => 2013,
            'filter' => 1
        ));

        /**
         * Check that there are only "Projet 1" assignments
         */
        $thisClosure = $this;
        $crawler->filter('table tbody tr.ressrow a.project-name')->each(function ($node, $i) use ($thisClosure) {
            $html = trim($node->html());

            $thisClosure->assertEquals('Projet 1', $html);
        });

        // Get the yearly summary with some parameters
        $crawler = $this->client->request('GET', $route, array(
            'week' => 49,
            'year' => 2013,
            'filter' => 2,
            'projects' => array(3)
        ));

        /**
         * Check that there are only "Projet 2" assignments
         */
        $crawler->filter('table tbody tr.ressrow a.project-name')->each(function ($node, $i) use ($thisClosure) {
            $html = trim($node->html());

            $thisClosure->assertEquals('Projet 2', $html);
        });
    }
}
