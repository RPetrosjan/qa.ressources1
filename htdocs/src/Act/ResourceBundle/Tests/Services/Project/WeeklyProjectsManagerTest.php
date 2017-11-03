<?php

namespace Act\ResourceBundle\Tests\Services\Project;

use Act\MainBundle\Tests\IsolatedTestCase;

class WeeklyProjectsManagerTest extends IsolatedTestCase
{
    public function testWeekPlanning()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $rum = $this->client->getContainer()->get('act_resource.weekly_projects_manager');

        $project1 = $em->getRepository('ActResourceBundle:Project')->find(1);
        $project2 = $em->getRepository('ActResourceBundle:Project')->find(2);
        $dates['start'] = \DateTime::createFromFormat('d/m/Y', '31/12/2013');
        $dates['end'] = \DateTime::createFromFormat('d/m/Y', '04/01/2014');

        $res1 = $rum->getWeekPlanning($project1, $dates['start'], $dates['end']);
        $res2 = $rum->getWeekPlanning($project2, $dates['start'], $dates['end']);

        // Test the project name
        $this->assertEquals("Congés", $res1->getData()['project']->getName());

        // Test the resource name
        $this->assertEquals('Développeur 1', $res1->getData()['resources'][1]['resource']->getName());
        $this->assertEquals('Développeur 4', $res2->getData()['resources'][4]['resource']->getName());

        // Test the Date assignment
        $this->assertEquals(67, $res2->getData()['resources'][4]['days']['03/01/2014']['assignment']->getId());
    }

    public function testWeekProject()
    {
        $rum = $this->client->getContainer()->get('act_resource.weekly_projects_manager');
        $dates['start'] = \DateTime::createFromFormat('d/m/Y', '31/12/2013');
        $dates['end'] = \DateTime::createFromFormat('d/m/Y', '04/01/2014');

        $res = $rum->getWeekProjects($dates['start'], $dates['end']);

        // Test the project name
        $this->assertEquals("Projet 1", $res[0]['name']);
        $this->assertEquals("Congés", $res[1]['name']);

        // Test the number of resources
        $this->assertEquals(1, count($res[0]['resources']));
        $this->assertEquals(1, count($res[1]['resources']));

        // Test the total of assignments
        $this->assertEquals(3, $res[0]['assignments']);

        // Test total of workload
        $this->assertEquals(3, $res[0]['total']);
    }
}