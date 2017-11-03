<?php

namespace Act\ResourceBundle\Tests\Controller;

use Act\MainBundle\Tests\CustomTestCase;

/**
 * Class YearSummaryControllerTest
 *
 * Contains all tests relative to the annual summary
 * of all resources workloads.
 */
class YearSummaryControllerTest extends CustomTestCase
{
    /**
     * Tests with the initial set of data
     */
    public function testInitialData()
    {
        $client = static::createClient();

        // Generate the route to the year summary page
        $route = $client->getContainer()->get('router')->generate('act_resource_year_summary');

        // Get the yearly summary with some parameters
        $crawler = $client->request('POST', $route, array(
            'start' => '20/01/2014',
            'monthsbefore' => 3,
            'monthsafter' => 3,
        ));

        /**
         * Check if the response if OK
         */
        $this->assertTrue($client->getResponse()->isSuccessful());

        /**
         * Check the number of week displayed is OK
         * 35 weeks + first one is the label "Week"
         */
        $this->assertCount(36, $crawler->filter('table thead tr.week-row td.week'));

        /**
         * Check the assignments for DEV3 - from W06 2014 to W13 2014
         */
        $workloads = array(-2, -0.1, 0, 1, 2, 3, 4, 5); $thisClosure = $this;
        $crawler->filter('table tbody tr')->reduce(function ($node, $i) {
            // Get only the 3rd row - the DEV3 assignments
            return($i == 2);
        })->filter('td')->reduce(function ($node, $i) {
            // Get only the W06 to W13
            return ($i >= 19 && $i <= 26 );
        })->each(function ($node, $i) use ($thisClosure, $workloads) {
            // Retrieve only the workload
            $workload = trim(preg_replace('!<\s*(span).*?>((.*?)</\1>)?!is', '', $node->html()));
            $thisClosure->assertEquals($workloads[$i], $workload);
        });

        /**
         * Check the assignments for DEV1 - W06 2014
         */
        $crawler->filter('table tbody tr')->reduce(function ($node, $i) {
            // Get only the 1st row - the DEV1 assignments
            return($i == 0);
        })->filter('td')->reduce(function ($node, $i) {
            // Get only the W06
            return ($i == 20); // because DEV1 is the first resource of the team and 1 more column
        })->each(function ($node, $i) use ($thisClosure) {
            // Retrieve only the workload
            $workload = trim(preg_replace('!<\s*(span).*?>((.*?)</\1>)?!is', '', $node->html()));
            $thisClosure->assertEquals(4, $workload);
        });

        /**
         * Check that there is a bankholiday for DEV1 - W44 2013
         */
        $crawler->filter('table tbody tr')->reduce(function ($node, $i) {
            // Get only the 1st row - the DEV1 assignments
            return($i == 0);
        })->filter('td')->reduce(function ($node, $i) {
            // Get only the W44
            return ($i == 6); // because DEV1 is the first resource of the team and 1 more column
        })->each(function ($node, $i) use ($thisClosure) {
            // Retrieve only the workload
            $workload = trim(preg_replace('!<\s*(span).*?>((.*?)</\1>)?!is', '', $node->html()));
            $thisClosure->assertEquals(4, $workload);
        });

        /**
         * Check that the DEV4 is only available from W49 2013 to W06 2014
         */
        $workloads = array(0.5, 2, -0.5, 1.5, -0.5, 0, 2.5, 2.5, 2.5);
        $crawler->filter('table tbody tr')->reduce(function ($node, $i) {
            // Get only the 4th row - the DEV4 assignments
            return($i == 3);
        })->filter('td')->reduce(function ($node, $i) {
            // Get from W40 to W22
            return ($i >= 1);
        })->each(function ($node, $i) use ($thisClosure, $workloads) {
            // Retrieve only the workload
            $workload = trim(preg_replace('!<\s*(span).*?>((.*?)</\1>)?!is', '', $node->html()));
            if ($i >= 9 && $i <= 17) {
                $thisClosure->assertEquals($workloads[($i - 9)], $workload);
            } else {
                $thisClosure->assertEquals('', $workload);
            }
        });

        /**
         * Check that the colors used to highlight workloads are right
         * for DEV3 - from W06 2014 to W13 2014
         */
        $classes = array(
            array('background-workload-high','workload-high'),
            array('background-workload-high','workload-high'),
            array('background-workload-ok','workload-ok'),
            array('background-workload-low','workload-low'),
            array('background-workload-vlow','workload-vlow'),
            array('background-workload-vlow','workload-vlow'),
            array('background-workload-vlow','workload-vlow'),
            array('background-workload-xlow','workload-xlow')
        );

        $crawler->filter('table tbody tr')->reduce(function ($node, $i) {
            // Get only the 3rd row - the DEV3 assignments
            return($i == 2);
        })->filter('td')->reduce(function ($node, $i) {
            // Get only the W06 to W13
            return ($i >= 19 && $i <= 26 );
        })->each(function ($node, $i) use ($thisClosure, $classes) {
            // Retrieve the node classes
            $nodeClasses = explode(' ', $node->attr('class'));
            $wantedClasses = $classes[$i];
            foreach ($wantedClasses as $class) {
                $thisClosure->assertContains($class, $nodeClasses);
            }
        });
    }
}
