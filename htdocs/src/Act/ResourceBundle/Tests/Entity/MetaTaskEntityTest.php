<?php

namespace Act\ResourceBundle\Tests\Entity;

use Act\ResourceBundle\Entity\MetaTask;

/**
 * Testing the MetaTask Entity
 */
class MetaTaskEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing shift()
     */
    public function testShift()
    {
        $task = new MetaTask();
        $task->setStart(new \DateTime('2014-02-20 00:00:00', new \DateTimeZone('Europe/Paris')));
        $task->setEnd(new \DateTime('2014-02-21 00:00:00', new \DateTimeZone('Europe/Paris')));

        // Shift 5 working days in past
        $task->shift(5, 0);
        $this->assertEquals(new \DateTime('2014-02-13 00:00:00', new \DateTimeZone('Europe/Paris')), $task->getStart());
        $this->assertEquals(new \DateTime('2014-02-14 23:59:59', new \DateTimeZone('Europe/Paris')), $task->getEnd());

        // Shift 10 working days in future
        $task->shift(10, 1);
        $this->assertEquals(new \DateTime('2014-02-27 00:00:00', new \DateTimeZone('Europe/Paris')), $task->getStart());
        $this->assertEquals(new \DateTime('2014-02-28 23:59:59', new \DateTimeZone('Europe/Paris')), $task->getEnd());

        // Try with an end of year date
        // 31/12/2013 - 01/01/2014
        $task->setStart(new \DateTime('2013-12-31 00:00:00', new \DateTimeZone('Europe/Paris')));
        $task->setEnd(new \DateTime('2014-01-01 23:59:59', new \DateTimeZone('Europe/Paris')));

        // Shift 8 days in future
        $task->shift(8, 1);
        $this->assertEquals(new \DateTime('2014-01-10 00:00:00', new \DateTimeZone('Europe/Paris')), $task->getStart());
        $this->assertEquals(new \DateTime('2014-01-13 23:59:59', new \DateTimeZone('Europe/Paris')), $task->getEnd());

        // Shift 20 days in past
        $task->shift(20, 0);
        $this->assertEquals(new \DateTime('2013-12-13 00:00:00', new \DateTimeZone('Europe/Paris')), $task->getStart());
        $this->assertEquals(new \DateTime('2013-12-16 23:59:59', new \DateTimeZone('Europe/Paris')), $task->getEnd());
    }
}
