<?php

namespace Act\ResourceBundle\Tests\Services;

use Act\MainBundle\Tests\IsolatedTestCase;

class ResourceUsageManagerTest extends IsolatedTestCase
{
    public function testChargeRate()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $rum = $this->client->getContainer()->get('act_resource.resources_usage_manager');

        $resource1 = $em->getRepository('ActResourceBundle:Resource')->find(1);
        $this->assertNotNull($resource1);

        $resource2 = $em->getRepository('ActResourceBundle:Resource')->find(3);
        $this->assertNotNull($resource2);

        $start = \DateTime::createFromFormat('d/m/Y', '30/12/2013');
        $end = \DateTime::createFromFormat('d/m/Y', '03/01/2014');

        $charge1 = $rum->getResourceChargeForPeriod($resource1, $start, $end, null, null);
        $charge2 = $rum->getResourceChargeForPeriod($resource2, $start, $end, null, null);

        // 5 affectations sur le projet congés
        // 5 jours de travail dispo
        $this->assertEquals(2, $charge1['affectedTime']);
        $this->assertEquals(5, $charge1['availableTime']);

        $this->assertEquals(0, $charge2['affectedTime']);
        $this->assertEquals(5, $charge2['availableTime']);
    }

    public function testChargeRateWithTags()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $rum = $this->client->getContainer()->get('act_resource.resources_usage_manager');

        $resource1 = $em->getRepository('ActResourceBundle:Resource')->find(1);
        $start = \DateTime::createFromFormat('d/m/Y', '30/12/2013');
        $end = \DateTime::createFromFormat('d/m/Y', '03/01/2014');

        $charge1 = $rum->getResourceChargeForPeriod($resource1, $start, $end, array('typeHoliday'), null);
        $this->assertEquals(2, $charge1['affectedTime']);
        $this->assertEquals(5, $charge1['availableTime']);

        $charge1 = $rum->getResourceChargeForPeriod($resource1, $start, $end, array('typeResearch'), null);
        $this->assertEquals(0, $charge1['affectedTime']);
        $this->assertEquals(5, $charge1['availableTime']);
    }

    public function testChargeRateWithProjects()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $rum = $this->client->getContainer()->get('act_resource.resources_usage_manager');

        $resource1 = $em->getRepository('ActResourceBundle:Resource')->find(1);
        $project2 = array('0'=>2);

        $start = \DateTime::createFromFormat('d/m/Y', '01/12/2013');
        $end = \DateTime::createFromFormat('d/m/Y', '06/12/2013');

        $charge1 = $rum->getResourceChargeForPeriod($resource1, $start, $end, null, $project2);
        $this->assertEquals(4, $charge1['affectedTime']);
        $this->assertEquals(5, $charge1['availableTime']);
    }

    public function testChargeRateWithTagsAndProjects()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $rum = $this->client->getContainer()->get('act_resource.resources_usage_manager');

        $resource1 = $em->getRepository('ActResourceBundle:Resource')->find(1);
        $project1 = array('0'=>1);

        $start1 = \DateTime::createFromFormat('d/m/Y', '30/12/2013');
        $end1 = \DateTime::createFromFormat('d/m/Y', '03/01/2014');

        $charge1 = $rum->getResourceChargeForPeriod($resource1, $start1, $end1, array('typeHoliday'), $project1);
        $this->assertEquals(2, $charge1['affectedTime']);
        $this->assertEquals(5, $charge1['availableTime']);
    }

    public function testChargeRateWithTagsNonActive()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $rum = $this->client->getContainer()->get('act_resource.resources_usage_manager');

        $resource1 = $em->getRepository('ActResourceBundle:Resource')->find(3);

        $start1 = \DateTime::createFromFormat('d/m/Y', '01/12/2013');
        $end1 = \DateTime::createFromFormat('d/m/Y', '07/12/2013');

        $charge1 = $rum->getResourceChargeForPeriod($resource1, $start1, $end1, array('typeInactive'));
        $this->assertEquals(1, $charge1['affectedTime']);
        $this->assertEquals(5, $charge1['availableTime']);
    }

    public function testWeeklyCharge()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $rum = $this->client->getContainer()->get('act_resource.resources_usage_manager');

        $resource1 = $em->getRepository('ActResourceBundle:Resource')->find(1);
        $start = \DateTime::createFromFormat('d/m/Y', '01/01/2014');
        $end = \DateTime::createFromFormat('d/m/Y', '16/01/2014');

        $toCheck = array(
            '01/2014' => array('affectedTime' => 2, 'availableTime' => 5),
            '02/2014' => array('affectedTime' => 3, 'availableTime' => 5),
            '03/2014' => array('affectedTime' => 0, 'availableTime' => 5),
        );

        $res = $rum->getResourceWeeklyChargeForPeriod($resource1, $start, $end);
        foreach ($res as $key => $week) {
            $this->assertEquals($toCheck[$key]['affectedTime'], $week['affectedTime']);
        }
    }

    public function testWeeklyChargeWithTags()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $rum = $this->client->getContainer()->get('act_resource.resources_usage_manager');

        $resource1 = $em->getRepository('ActResourceBundle:Resource')->find(1);
        $start = \DateTime::createFromFormat('d/m/Y', '01/01/2014');
        $end = \DateTime::createFromFormat('d/m/Y', '16/01/2014');
        $tags = array('typeResearch', 'typeInternal');

        $toCheck = array(
            '01/2014' => array('affectedTime' => 0, 'availableTime' => 5),
            '02/2014' => array('affectedTime' => 0, 'availableTime' => 5),
            '03/2014' => array('affectedTime' => 0, 'availableTime' => 5),
        );
        $res = $rum->getResourceWeeklyChargeForPeriod($resource1, $start, $end, $tags);
        foreach ($res as $key => $week) {
            $this->assertEquals($toCheck[$key]['affectedTime'], $week['affectedTime']);
        }
    }

    public function testWeeklyChargeWithProject()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $rum = $this->client->getContainer()->get('act_resource.resources_usage_manager');

        $resource1 = $em->getRepository('ActResourceBundle:Resource')->find(1);
        $start = \DateTime::createFromFormat('d/m/Y', '01/01/2014');
        $end = \DateTime::createFromFormat('d/m/Y', '16/01/2014');
        $projects = array(1);

        $toCheck = array(
            '01/2014' => array('affectedTime' => 2, 'availableTime' => 5),
            '02/2014' => array('affectedTime' => 3, 'availableTime' => 5),
            '03/2014' => array('affectedTime' => 0, 'availableTime' => 5),
        );
        $res = $rum->getResourceWeeklyChargeForPeriod($resource1, $start, $end, null, $projects);
        foreach ($res as $key => $week) {
            $this->assertEquals($toCheck[$key]['affectedTime'], $week['affectedTime']);
        }
    }

    public function testWeeklyChargeWithTagsAndProject()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $rum = $this->client->getContainer()->get('act_resource.resources_usage_manager');

        $resource1 = $em->getRepository('ActResourceBundle:Resource')->find(3);
        $start = \DateTime::createFromFormat('d/m/Y', '01/01/2014');
        $end = \DateTime::createFromFormat('d/m/Y', '16/02/2014');
        $tags = array('typeHoliday');
        $projects = array(1);

        $toCheck = array(
            '01/2014' => array('affectedTime' => 0, 'availableTime' => 5),
            '02/2014' => array('affectedTime' => 0, 'availableTime' => 5),
            '03/2014' => array('affectedTime' => 0, 'availableTime' => 5),
            '04/2014' => array('affectedTime' => 0, 'availableTime' => 5),
            '05/2014' => array('affectedTime' => 0, 'availableTime' => 5),
            '06/2014' => array('affectedTime' => 1, 'availableTime' => 5),
            '07/2014' => array('affectedTime' => 0.10, 'availableTime' => 5),
        );
        $res = $rum->getResourceWeeklyChargeForPeriod($resource1, $start, $end, $tags, $projects);
        foreach ($res as $key => $week) {
            $this->assertEquals($toCheck[$key]['affectedTime'], $week['affectedTime']);
        }
    }

    public function testWeeklyChargeWithTagsNonActive()
    {
        $em = $this->client->getContainer()->get('doctrine')->getManager();
        $rum = $this->client->getContainer()->get('act_resource.resources_usage_manager');

        $resource1 = $em->getRepository('ActResourceBundle:Resource')->find(3);
        $start = \DateTime::createFromFormat('d/m/Y', '25/11/2013');
        $end = \DateTime::createFromFormat('d/m/Y', '14/12/2013');
        $tags = array('typeInactive');

        $toCheck = array(
            '48/2013' => array('affectedTime' => 0, 'availableTime' => 5),
            '49/2013' => array('affectedTime' => 1, 'availableTime' => 5),
            '50/2013' => array('affectedTime' => 0, 'availableTime' => 5),
        );
        $res = $rum->getResourceWeeklyChargeForPeriod($resource1, $start, $end, $tags, null);
        foreach ($res as $key => $week) {
            $this->assertEquals($toCheck[$key]['affectedTime'], $week['affectedTime']);
        }
    }
}
