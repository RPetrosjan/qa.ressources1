<?php

namespace Act\ResourceBundle\Tests\Command;

use Act\MainBundle\Tests\IsolatedTestCase;
use Act\ResourceBundle\Command\SendPrevisionalMailCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application as App;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class SendPrevisionalMailCommandTest
 *
 * Testing the command that send periodically
 * the summary of assignments for subscribed teams.
 *
 * @package Act\ResourceBundle\Tests\Command
 */
class SendPrevisionalMailCommandTest extends IsolatedTestCase
{
    /**
     * Check that the mail is sent if there is one subscriber
     */
    public function testExecuteWithOneSubscriber()
    {
        // Adds a subscriber for the Tech team
        $team = $this->em->getRepository('ActResourceBundle:Team')->findOneBy(array('name' => 'S. Tech'));
        $user = $this->em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(array('username' => 'ressources.admin'));
        $user->addPrevisionalTeam($team);
        $this->em->flush();

        // Initialize application
        $application = new App($this->client->getKernel());
        $application->add(new SendPrevisionalMailCommand());

        // Get and execute command
        $command = $application->find('act:resources:mail:previsional:send');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        // Check if we the mail was properly sent
        $this->assertRegExp('/1 mail\(s\) sent out of 1/', $commandTester->getDisplay(), 'The mail was not properly sent');
    }

    /**
     * Check that there is no subscribers by default
     */
    public function testExecuteWithNoSubscribers()
    {
        // Initialize application
        $application = new App($this->client->getKernel());
        $application->add(new SendPrevisionalMailCommand());

        // Get and execute command
        $command = $application->find('act:resources:mail:previsional:send');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        // Check if there are no changes found
        $this->assertRegExp('/0 mail\(s\) sent out of 0/', $commandTester->getDisplay(), 'There should be no subscribers by default - check fixtures or test insulation');
    }
}
