<?php

namespace Act\MainBundle\Tests\Services;

use Act\MainBundle\Services\DateManager;

/**
 * Unit tests relative to the DateManager service
 */
class DateManagerTest extends \PHPUnit_Framework_TestCase
{
    private $tm;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->tm = new DateManager();
    }

    /**
     * Testing the findFirstAndLastDaysOfWeek() function
     */
    public function testFindFirstAndLastDaysOfWeek()
    {
        // Tests data set
        $data = array(
            'simple' => array(
                'date' => new \DateTime('2013-11-13 00:00:00', new \DateTimeZone('Europe/Paris')),
                'start' => new \DateTime('2013-11-11', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2013-11-15 23:59:59', new \DateTimeZone('Europe/Paris'))
            ),
            'simpleWE' => array(
                'date' => new \DateTime('2013-11-16 00:00:00', new \DateTimeZone('Europe/Paris')),
                'start' => new \DateTime('2013-11-11', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2013-11-15 23:59:59', new \DateTimeZone('Europe/Paris'))
            ),
            'simpleMonday' => array(
                'date' => new \DateTime('2013-11-11 00:00:00', new \DateTimeZone('Europe/Paris')),
                'start' => new \DateTime('2013-11-11', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2013-11-15 23:59:59', new \DateTimeZone('Europe/Paris'))
            ),
            'endOfMonth' => array(
                'date' => new \DateTime('2013-10-31 00:00:00', new \DateTimeZone('Europe/Paris')),
                'start' => new \DateTime('2013-10-28', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2013-11-01 23:59:59', new \DateTimeZone('Europe/Paris'))
            ),
            'endOfMonthWE' => array(
                'date' => new \DateTime('2013-10-28 00:00:00', new \DateTimeZone('Europe/Paris')),
                'start' => new \DateTime('2013-10-28', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2013-11-01 23:59:59', new \DateTimeZone('Europe/Paris'))
            ),
            'endOfMonthMonday' => array(
                'date' => new \DateTime('2013-11-03 00:00:00', new \DateTimeZone('Europe/Paris')),
                'start' => new \DateTime('2013-10-28', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2013-11-01 23:59:59', new \DateTimeZone('Europe/Paris'))
            ),
            'endOfYear' => array(
                'date' => new \DateTime('2013-12-31 00:00:00', new \DateTimeZone('Europe/Paris')),
                'start' => new \DateTime('2013-12-30', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2014-01-03 23:59:59', new \DateTimeZone('Europe/Paris'))
            ),
            'endOfYearWE' => array(
                'date' => new \DateTime('2014-01-05 00:00:00', new \DateTimeZone('Europe/Paris')),
                'start' => new \DateTime('2013-12-30', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2014-01-03 23:59:59', new \DateTimeZone('Europe/Paris'))
            )
        );

        // Throwing tests
        foreach ($data as $test) {
            $result = $this->tm->findFirstAndLastDaysOfWeek($test['date']);
            $this->assertEquals($result['start'], $test['start']);
            $this->assertEquals($result['end'], $test['end']);
        }
    }

    /**
     * Testing the findDateWithYearAndWeek() function
     */
    public function testFindDateWithYearAndWeek()
    {
        $data = array(
            0 => array(
                'week' => 48,
                'year' => 2013,
                'date' => new \DateTime('2013-11-25 00:00:00', new \DateTimeZone('Europe/Paris'))
            ),
            1 => array(
                'week' => 52,
                'year' => 2013,
                'date' => new \DateTime('2013-12-23 00:00:00', new \DateTimeZone('Europe/Paris'))
            ),
            2 => array(
                'week' => 01,
                'year' => 2014,
                'date' => new \DateTime('2013-12-30 00:00:00', new \DateTimeZone('Europe/Paris'))
            ),
            3 => array(
                'week' => 02,
                'year' => 2014,
                'date' => new \DateTime('2014-01-06 00:00:00', new \DateTimeZone('Europe/Paris'))
            )
        );

        // Throwing tests
        foreach ($data as $test) {
            $this->assertEquals($test['date'], $this->tm->findDateWithYearAndWeek($test['week'], $test['year']));
        }
    }

    /**
     * Testing the getNbWorkingDaysBetween() function
     */
    public function testGetNbWorkingDaysBetween()
    {
        $data = array(
            0 => array(
                'start' => new \DateTime('2013-12-23 00:00:00', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2014-01-05 00:00:00', new \DateTimeZone('Europe/Paris')),
                'nb' => 10
            ),
            1 => array(
                'start' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2014-01-31 00:00:00', new \DateTimeZone('Europe/Paris')),
                'nb' => 23
            ),
            2 => array(
                'start' => new \DateTime('2014-02-01 00:00:00', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2014-02-02 00:00:00', new \DateTimeZone('Europe/Paris')),
                'nb' => 0
            ),
            3 => array(
                'start' => new \DateTime('2014-01-03 00:00:00', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2013-12-30 00:00:00', new \DateTimeZone('Europe/Paris')),
                'nb' => 5
            )
        );

        // Throwing tests
        foreach ($data as $test) {
            $this->assertEquals($test['nb'], $this->tm->getNbWorkingDaysBetween($test['start'], $test['end']));
        }
    }

    /**
     * Testing the belongsToCurrentWeek() function
     */
    public function testBelongsToCurrentWeek()
    {
        $this->assertTrue($this->tm->belongsToCurrentWeek(new \DateTime("now")));
    }

    /**
     * Testing the findFirstAndLastDaysOfMonth() function
     */
    public function testFindFirstAndLastDaysOfMonth()
    {
        // Tests data set
        $data = array(
            'february28' => array(
                'date' => new \DateTime('2014-02-14 00:00:00', new \DateTimeZone('Europe/Paris')),
                'start' => new \DateTime('2014-02-01 00:00:00', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2014-02-28 23:59:59', new \DateTimeZone('Europe/Paris'))
            ),
            'february29' => array(
                'date' => new \DateTime('2016-02-26 00:00:00', new \DateTimeZone('Europe/Paris')),
                'start' => new \DateTime('2016-02-01 00:00:00', new \DateTimeZone('Europe/Paris')),
                'end' => new \DateTime('2016-02-29 23:59:59', new \DateTimeZone('Europe/Paris'))
            )
        );

        // Throwing tests
        foreach ($data as $test) {
            $result = $this->tm->findFirstAndLastDaysOfMonth($test['date']);
            $this->assertEquals($result['start'], $test['start']);
            $this->assertEquals($result['end'], $test['end']);
        }
    }
}
