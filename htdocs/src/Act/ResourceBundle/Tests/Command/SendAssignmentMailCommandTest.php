<?php

namespace Act\ResourceBundle\Tests\Command;

use Act\MainBundle\Tests\IsolatedTestCase;
use Act\ResourceBundle\Command\SendAssignmentMailCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application as App;
use Symfony\Component\Console\Tester\CommandTester;
use Act\ResourceBundle\Entity\Assignment;

/**
 * Class SendAssignmentMailCommandTest
 *
 * Testing the command that send periodically
 * the summary of assignments for resources
 * that had a change in their week assignments.
 *
 * @package Act\ResourceBundle\Tests\Command
 */
class SendAssignmentMailCommandTest extends IsolatedTestCase
{
    /**
     * Check that the mail is sent if there is a change for one resource
     */
    public function testExecuteWithChanges()
    {
        // Adds an assignment for the DEV1
        $assignment = new Assignment();
        $assignment->setProject($this->em->getRepository('ActResourceBundle:Project')->findOneBy(array('name' => 'Projet 1')));
        $assignment->setDay(new \DateTime('now'));
        $assignment->setResource($this->em->getRepository('ActResourceBundle:Resource')->findOneBy(array('name' => 'Développeur 1')));
        $assignment->setWorkload(1);

        $this->em->persist($assignment);
        $this->em->flush();

        // Initialize application
        $application = new App($this->client->getKernel());
        $application->add(new SendAssignmentMailCommand());

        // Get and execute command
        $command = $application->find('act:resources:mail:assignment:send');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        // Check if we found the change for one resource
        $this->assertRegExp('/1 resource\(s\) found/', $commandTester->getDisplay(), 'There should be one resource with changes here');

        // Check if we the mail was properly sent
        $this->assertRegExp('/1 mail\(s\) sent out of 1/', $commandTester->getDisplay(), 'The mail was not properly sent');
    }

    /**
     * Check that there is no changed assignments by default
     */
    public function testExecuteWithNoChanges()
    {
        // Initialize application
        $application = new App($this->client->getKernel());
        $application->add(new SendAssignmentMailCommand());

        // Get and execute command
        $command = $application->find('act:resources:mail:assignment:send');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        // Check if there are no changes found
        $this->assertRegExp('/No resource found/', $commandTester->getDisplay(), 'There should be no new assignments by default - check fixtures or test insulation');
    }
}
