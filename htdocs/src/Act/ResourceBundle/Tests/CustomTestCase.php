<?php

namespace Act\ResourceBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * Helper class to add custom behaviour before/after
 * any tests in our application bundle
 */
abstract class CustomTestCase extends WebTestCase implements TypeTestCaseInterface
{
    protected $client;
    protected $em;
    protected $application;

    protected $adminUsername = 'ressources.admin';
    protected $adminPasswd = 'Actency13*';

    protected $userUsername = 'ressources.user';
    protected $userPasswd = 'Actency13*';

    /**
     * Called at the begining of the test
     */
    protected function setUp()
    {
        parent::setUp();

        // Setup authentication options if needed
        $options = array();
        if ($this->mustAuthentify()) {
            $options = array(
              'PHP_AUTH_USER' => $this->adminUsername,
              'PHP_AUTH_PW'   => $this->adminPasswd,
            );
        }

        // Create client
        $this->client = $this->createClient(array(), $options);

        // Create application
        $this->application = new Application($this->client->getKernel());
        $this->application->setAutoExit(false);

        // Set up the entity manager
        $this->em = static::$kernel->getContainer()
          ->get('doctrine')
          ->getManager();
    }

    /**
     * Called at the end of the test
     */
    protected function tearDown()
    {
        parent::tearDown();

        // Close entity manager
        $this->em->close();

        // Reset database if needed
        if ($this->mustResetDatabase()) {
            self::runCommand('doctrine:schema:drop --force');
            self::runCommand('doctrine:schema:update --force');
            self::runCommand('doctrine:fixtures:load --no-interaction');
        }
    }

    /**
     * Helper function to throw commands
     * @param  string  $command the command name
     * @return nothing
     */
    protected function runCommand($command)
    {
        $command = sprintf('%s --quiet', $command);

        return $this->application->run(new StringInput($command));
    }
}
