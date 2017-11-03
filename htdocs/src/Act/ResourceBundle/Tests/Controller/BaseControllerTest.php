<?php

namespace Act\ResourceBundle\Tests\Controller;

use Act\MainBundle\Tests\CustomTestCase;
use Act\ResourceBundle\Entity\Assignment;
use Act\ResourceBundle\Entity\BankHoliday;

/**
 * Class BaseControllerTest
 *
 * Contains all tests relative to the base controller
 */
class BaseControllerTest extends CustomTestCase
{
    /**
     * Tests relative to the user dashboard
     */
    public function testDashboard()
    {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $dates = $client->getContainer()->get('act_main.date.manager')->findFirstAndLastDaysOfWeek();

        $em->beginTransaction();

        // Load the homepage
        $client->request('GET', '/fr');

        // Load useful entities
        $project1 = $em->getRepository('ActResourceBundle:Project')->find(2);
        $project2 = $em->getRepository('ActResourceBundle:Project')->find(3);
        $resource = $em->getRepository('ActResourceBundle:Resource')->find(1);

        // Create some assignments
        $assignment1 = new Assignment();
        $assignment1->setProject($project1);
        $assignment1->setDay($dates['start']);
        $assignment1->setResource($resource);
        $assignment1->setWorkload(1);
        $em->persist($assignment1);

        $assignment2 = new Assignment();
        $assignment2->setProject($project1);
        $assignment2->setDay($dates['end']);
        $assignment2->setResource($resource);
        $assignment2->setWorkload(0.5);
        $em->persist($assignment2);

        $assignment3 = new Assignment();
        $assignment3->setProject($project2);
        $assignment3->setDay($dates['end']);
        $assignment3->setResource($resource);
        $assignment3->setWorkload(0.5);
        $em->persist($assignment3);

        // Create a bankholiday
        $bankholiday = new BankHoliday();
        $bankholiday->addLocation($resource->getLocation());
        $bankholiday->setName('Testing bankholiday');
        $bankholiday->setStart($dates['start']->modify('+1 day'));
        $em->persist($bankholiday);
        $em->flush();

        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();

        /**
         * Check if the page loaded successfully
         */
        $this->assertCount(2, $crawler->filter('table#assignments tbody tr'));

        /**
         * Check if we find the right assignments at the right places
         */
        $thisClosure = $this;
        $crawler->filter('table#assignments tbody tr')->each(function ($node, $i) use ($thisClosure) {
            if ($i == 0) {
                // First line, project 1, check first and last dates
                $node->filter('td')->reduce(function ($node, $i) {
                    return ($i == 1 || $i == 2 || $i == 5);
                })->each(function ($node, $i) use ($thisClosure) {
                    $txt = trim(strip_tags($node->html()));
                    if ($i == 0) {
                        $thisClosure->assertEquals('7h', $txt);
                    } elseif ($i == 1) {
                        $thisClosure->assertEquals('Testing bankholiday', $txt);
                    } else {
                        $thisClosure->assertEquals('3h30', $txt);
                    }
                });
            } else {
                // Second line, project 2, check last date
                $node->filter('td')->reduce(function ($node, $i) {
                    return ($i == 2 || $i == 5);
                })->each(function ($node, $i) use ($thisClosure) {
                    $txt = trim(strip_tags($node->html()));
                    if ($i == 0) {
                        $thisClosure->assertEquals('Testing bankholiday', $txt);
                    } else {
                        $thisClosure->assertEquals('3h30', $txt);
                    }
                });
            }
        });

        $em->rollback();
    }

    /**
     * Tests relative to the login and first login process
     */
    public function testLogin()
    {
        $client = static::createClient(array(), array(), true);
        $em = $client->getContainer()->get('doctrine')->getManager();

        $em->beginTransaction();

        // Load the homepage
        $client->request('GET', '/fr');
        $crawler = $client->followRedirect();

        /**
         * Check if there is a link to the login page in fr locale
         */
        $this->assertCount(1, $crawler->filter('#language-selector a.locale-fr'));

        // Get the link to the login page in locale FR and follow it
        $link = $crawler->filter('#language-selector a.locale-fr')->link();
        $crawler = $client->click($link);

        /**
         * Check if there is the login form
         */
        $this->assertCount(1, $crawler->filter('form.login-form'));

        // Find the login form, fill it and submit it
        $form = $crawler->filter('form.login-form')->form(array(
            '_username'  => 'ressources.cpt',
            '_password'  => 'Actency13*',
        ));
        $client->submit($form);

        /**
         * Check if the response is a redirect to login_check
         */
        $route = $client->getRequest()->attributes->get('_route');
        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertEquals('fos_user_security_check', $route);
        $client->followRedirect();

        /**
         * Check if the response is a redirect for locale
         */
        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();

        // Follow all redirects
        while ($client->getResponse()->isRedirect()) {
            $crawler = $client->followRedirect();
        }

        /**
         * Check if the response is the first login page
         */
        $route = $client->getRequest()->attributes->get('_route');
        $this->assertEquals('act_resource_user_first_login', $route);

        /**
         * Check if we find the form
         */
        $this->assertEquals(1, $crawler->filter('form#linkresource')->count());

        // Get the choose a resource form
        $form = $crawler->filter('form#linkresource')->form();
        $form['resource']->select(4); // Set DEV4 resource
        $client->submit($form);

        /**
         * Check if the response is a redirect to homepage
         */
        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();

        /**
         * Check if the text appears on the page
         */
        $this->assertGreaterThan(0, $crawler->filter('h1:contains("ressources.cpt")')->count());

        $em->rollback();
    }
}
