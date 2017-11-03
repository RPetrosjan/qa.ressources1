<?php

namespace Act\ResourceBundle\Tests\Entity;

use Act\ResourceBundle\Entity\Assignment;
use Act\ResourceBundle\Entity\CommonTask;

/**
 * Testing the Assignment Entity
 */
class AssignmentEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing shift()
     */
    public function testShift()
    {
        $assignment = new Assignment();
        $assignment->setDay(new \DateTime('2014-02-20 00:00:00', new \DateTimeZone('Europe/Paris')));

        // Shift 5 working days in past
        $assignment->shift(5, 0);
        $this->assertEquals(new \DateTime('2014-02-13 00:00:00', new \DateTimeZone('Europe/Paris')), $assignment->getDay());

        // Shift 10 working days in future
        $assignment->shift(10, 1);
        $this->assertEquals(new \DateTime('2014-02-27 00:00:00', new \DateTimeZone('Europe/Paris')), $assignment->getDay());

        // Try with an end of year date
        // 31/12/2013
        $assignment->setDay(new \DateTime('2013-12-31 00:00:00', new \DateTimeZone('Europe/Paris')));

        // Shift 8 days in future
        $assignment->shift(8, 1);
        $this->assertEquals(new \DateTime('2014-01-10 00:00:00', new \DateTimeZone('Europe/Paris')), $assignment->getDay());

        // Shift 20 days in past
        $assignment->shift(20, 0);
        $this->assertEquals(new \DateTime('2013-12-13 00:00:00', new \DateTimeZone('Europe/Paris')), $assignment->getDay());
    }

    /**
     * Testing getUnsold()
     */
    public function testGetUnsold()
    {
        $assignment = new Assignment();
        $commonTask = new CommonTask();
        $assignment->setWorkload(1);
        $assignment->setDay(new \DateTime('2014-02-20 00:00:00', new \DateTimeZone('Europe/Paris')));

        // No task assigned, assignment is unsold
        $this->assertEquals(1, $assignment->getUnsold());

        // CommonTask assigned, with a workload sold of 1
        // So there should be no unsold
        $commonTask->addAssignment($assignment);
        $commonTask->setWorkloadSold(1);
        $this->assertEquals(0, $assignment->getUnsold());

        // CommonTask with a workload sold of 0.5
        // There should be 0.5 unsold
        $commonTask->setWorkloadSold(0.5);
        $this->assertEquals(0.5, $assignment->getUnsold());

        // Test case when there are other sold assignments before this one
        // $assignment2 is sold, but $assignment is unsold
        $assignment2 = new Assignment();
        $assignment->setDay(new \DateTime('2014-02-19 00:00:00', new \DateTimeZone('Europe/Paris')));
        $assignment2->setWorkload(1);
        $commonTask->setWorkloadSold(1);
        $commonTask->addAssignment($assignment2);
        $this->assertEquals(0, $assignment2->getUnsold());
        $this->assertEquals(1, $assignment->getUnsold());

        // Check if we set the workloadSold of the task to 2
        $commonTask->setWorkloadSold(2);
        $this->assertEquals(0, $assignment2->getUnsold());
        $this->assertEquals(0, $assignment->getUnsold());

        // Now check if the $assignment can be partially unsold
        $commonTask->setWorkloadSold(1.5);
        $this->assertEquals(0, $assignment2->getUnsold());
        $this->assertEquals(0.5, $assignment->getUnsold());

        $commonTask->setWorkloadSold(1);
        $assignment2->setWorkload(2);
        $this->assertEquals(1, $assignment2->getUnsold());
        $this->assertEquals(1, $assignment->getUnsold());
    }

    /**
     * Testing addWorkload()
     */
    public function testAddWorkload()
    {
        $assignment = new Assignment();
        $assignment->setWorkload(1);
        $assignment->addWorkload(0.5);
        $this->assertEquals(1.5, $assignment->getWorkload());

        $assignment->addWorkload(-0.5);
        $this->assertEquals(1, $assignment->getWorkload());
    }
}
