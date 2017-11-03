<?php

namespace Act\MainBundle\Tests\Services;

use Act\MainBundle\Services\TimeManager;

/**
 * Unit tests relative to the TimeManager service
 */
class TimeManagerTest extends \PHPUnit_Framework_TestCase
{
    private $tm;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->tm = new TimeManager();
    }

    /**
     * Testing workloadFormat()
     */
    public function testWorkloadFormat()
    {
        $this->assertEquals('7h', $this->tm->workloadFormat(1));
        $this->assertEquals('3h30', $this->tm->workloadFormat(0.5));
        $this->assertEquals('1h45', $this->tm->workloadFormat(0.25));
        $this->assertEquals('5h15', $this->tm->workloadFormat(0.75));
        $this->assertEquals('45min', $this->tm->workloadFormat(0.1));
        $this->assertEquals('42min', $this->tm->workloadFormat(0.1, 7, false));
    }
}