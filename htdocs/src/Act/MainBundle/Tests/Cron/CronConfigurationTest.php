<?php

namespace Act\MainBundle\Tests\Cron;

use Symfony\Component\Process\Process;

/**
 * Unit tests the cron configuration
 * for sending assignment mails.
 */
class CronConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing the CRON configuration of
     * the send assignment mails command.
     */
    public function testSendAssignmentMail()
    {
        $process = new Process('crontab -l');
        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->markTestIncomplete('CRON configuration issue : ' . $process->getErrorOutput());
        } else {
            $output = $process->getOutput();
            if (strpos($output, 'act:resources:mail:assignment:send') === FALSE) {
                $this->markTestIncomplete('The CRON for the send assignments is not set.');
            }
        }
    }
}
