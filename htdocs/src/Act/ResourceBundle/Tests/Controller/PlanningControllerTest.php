<?php

namespace Act\ResourceBundle\Tests\Controller;

use Act\MainBundle\Tests\CustomTestCase;

/**
 * Class PlanningControllerTest
 *
 * Contains all tests relative to the project planning.
 *
 * @package Act\ResourceBundle\Tests\Controller
 */
class PlanningControllerTest extends CustomTestCase
{
    /**
     * Tests with the initial set of data
     */
    public function testInitialData()
    {
        $client = static::createClient();

        // Generate the route to the project planning page
        $route = $client->getContainer()->get('router')->generate('act_resource_project_show', array('id' => 2));
        $crawler = $client->request('GET', $route, array(
            'start' => '25/11/2013',
            'end' => '27/12/2013'
        ));

        /**
         * Check the number of days displayed is OK
         * 25 days
         */
        $this->assertCount(25, $crawler->filter('table.planning-table thead tr th.day'));

        /**
         * Check the assignments for DEV1 - from 02/12/2013 to 09/12/2013
         * + Check the CSS classes for same period and resource
         */
        $thisClosure = $this;
        $workloads = array(0.25, 0.5, 0.75, 1, 1.5, 2);
        $classes = array(
            array('assignment', 'first-of-week', 'no-task-assigned', 'background-workload-vlow'),
            array('assignment', 'no-task-assigned', 'background-workload-low'),
            array('assignment', 'no-task-assigned', 'background-workload-ok'),
            array('assignment', 'no-task-assigned', 'background-workload-ok'),
            array('assignment', 'last-of-week', 'no-task-assigned', 'day-wk-error', 'background-workload-high'),
            array('assignment', 'first-of-week', 'no-task-assigned', 'day-wk-error', 'background-workload-critical'),
        );

        $crawler->filter('table.planning-table tbody tr')->reduce(function ($node, $i) {
            // Get only the 1st row - the DEV1 assignments
            return($i == 0);
        })->filter('td')->reduce(function ($node, $i) {
            // Get only from 02/12/2013 to 09/12/2013
            return ($i >= 5 && $i <= 10);
        })->each(function ($node, $i) use ($thisClosure, $workloads, $classes) {
            // Retrieve the workload
            $workload = $node->filter('span.workload')->html();
            $thisClosure->assertEquals($workloads[$i], $workload);

            // Retrieve the CSS class
            $nodeClasses = explode(' ', $node->attr('class'));
            $wantedClasses = $classes[$i];
            foreach ($wantedClasses as $class) {
                $thisClosure->assertContains($class, $nodeClasses);
            }
        });

        /**
         * Check the assignments for DEV3 - from 02/12/2013 to 05/12/2013
         * + Check the CSS classes for same period and resource
         */
        $workloads = array(0.2, 0.2, 0.5, 1);
        $classes = array(
            array('assignment', 'first-of-week', 'no-task-assigned', 'day-wk-error', 'background-workload-high'),
            array('assignment', 'no-task-assigned', 'background-workload-vlow'),
            array('assignment', 'no-task-assigned', 'background-workload-ok'),
            array('assignment', 'no-task-assigned', 'background-workload-ok')
        );

        $crawler->filter('table.planning-table tbody tr')->reduce(function ($node, $i) {
            // Get only the 2nd row - the DEV3 assignments
            return($i == 1);
        })->filter('td')->reduce(function ($node, $i) {
            // Get only from 02/12/2013 to 05/12/2013
            return ($i >= 5 && $i <= 8);
        })->each(function ($node, $i) use ($thisClosure, $workloads, $classes) {
            // Retrieve the workload
            $workload = $node->filter('span.workload')->html();
            $thisClosure->assertEquals($workloads[$i], $workload);

            // Retrieve the CSS class
            $nodeClasses = explode(' ', $node->attr('class'));
            $wantedClasses = $classes[$i];
            foreach ($wantedClasses as $class) {
                $thisClosure->assertContains($class, $nodeClasses);
            }
        });

        /**
         * Check the assignments for DEV4 - from 02/12/2013 to 09/12/2013
         * + Check the CSS classes for same period and resource
         */
        $workloads = array(0.5, 0.5, 0.5, '', 0.5, 0.5);
        $classes = array(
            array('assignment', 'first-of-week', 'task-assigned', 'background-workload-low'),
            array('assignment', 'task-assigned', 'background-workload-low'),
            array('assignment', 'task-assigned', 'subtask-assigned', 'out-of-subtask', 'background-workload-low'),
            array(),
            array('assignment', 'last-of-week', 'no-task-assigned', 'background-workload-low'),
            array('assignment', 'first-of-week', 'task-assigned', 'out-of-task', 'background-workload-low')
        );

        $crawler->filter('table.planning-table tbody tr')->reduce(function ($node, $i) {
            // Get only the 3rd row - the DEV4 assignments
            return($i == 2);
        })->filter('td')->reduce(function ($node, $i) {
            // Get only from 02/12/2013 to 09/12/2013
            return ($i >= 5 && $i <= 10);
        })->each(function ($node, $i) use ($thisClosure, $workloads, $classes) {
            // Retrieve the workload
            $workload = $node->filter('span.workload')->html();
            $thisClosure->assertEquals($workloads[$i], $workload);

            // Retrieve the CSS class
            $nodeClasses = explode(' ', $node->attr('class'));
            $wantedClasses = $classes[$i];
            foreach ($wantedClasses as $class) {
                $thisClosure->assertContains($class, $nodeClasses);
            }
        });

        /**
         * Check the bankholidays on 25/12 + 26/12 for the 3 Devs
         */
        $crawler->filter('table.planning-table tbody tr')->reduce(function ($node, $i) {
            // Get only the 3 first rows
            return($i >= 0 && $i <= 2);
        })->each(function ($node, $ii) use ($thisClosure) {
            $node->filter('td')->reduce(function ($node, $i) {
                return ($i >= 22 && $i <= 23);
            })->each(function ($node, $i) use ($ii, $thisClosure) {
                // Retrieve the CSS class
                $nodeClasses = explode(' ', $node->attr('class'));

                if ($ii == 0) {
                    $thisClosure->assertContains('bankholiday', $nodeClasses);
                } else {
                    if ($i == 0) {
                        $thisClosure->assertContains('bankholiday', $nodeClasses);
                    } else {
                        $thisClosure->assertNotContains('bankholiday', $nodeClasses);
                    }
                }
            });
        });

        /**
         * Check that the DEV4 is unavailable from 25/11 to 19/11
         */
        $crawler->filter('table.planning-table tbody tr')->reduce(function ($node, $i) {
            // Get only the 3rd row - the DEV4 assignments
            return($i == 2);
        })->filter('td')->reduce(function ($node, $i) {
            // Get only from 25/11/2013 to 29/11/2013
            return ($i >= 0 && $i <= 4);
        })->each(function ($node, $i) use ($thisClosure) {
            // Retrieve the CSS class
            $nodeClasses = explode(' ', $node->attr('class'));
            $thisClosure->assertContains('disabled', $nodeClasses);
        });
    }

    /**
     * Test that a disabled project displays its assignments
     */
    public function testDisabledProject()
    {
        $client = static::createClient();

        // Generate the route to the project planning page
        $route = $client->getContainer()->get('router')->generate('act_resource_project_show', array('id' => 4));
        $crawler = $client->request('GET', $route, array(
            'start' => '25/11/2013',
            'end' => '27/12/2013'
        ));

        $thisClosure = $this;
        $crawler->filter('table.planning-table tbody tr')->reduce(function ($node, $i) {
            // Get only the 2nd row - the DEV3 assignments
            return($i == 1);
        })->filter('td')->reduce(function ($node, $i) {
            // Get only 05/12/2013
            return ($i == 8);
        })->each(function ($node, $i) use ($thisClosure) {
            // Retrieve the workload
            $workload = $node->filter('span.workload')->html();
            $thisClosure->assertEquals(1, $workload);
        });
    }
}
