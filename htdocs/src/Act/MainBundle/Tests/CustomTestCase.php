<?php

namespace Act\MainBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

/**
 * Parent class for functionnal tests
 *
 * Child classes must inherit from this class
 * as it provides the method for creating the
 * test client and take advantage of database
 * isolation for the whole test.
 *
 * The database connection object is shared
 * in order to be able to do that.
 */
abstract class CustomTestCase extends WebTestCase
{
    protected static $adminUsername = 'ressources.admin';
    protected static $adminPasswd = 'Actency13*';

    protected static $userUsername = 'ressources.user';
    protected static $userPasswd = 'Actency13*';

    /**
     * If true, reset the database after all this class tests
     */
    protected static $resetDatabaseAfterTests = false;

    /**
     * Called at the end of the class tests
     */
    public static function tearDownAfterClass()
    {
        // Reset database if needed
        if (static::$resetDatabaseAfterTests) {
            echo('R'); // Reset database indicator

            $client = static::createClient();
            $application = new Application($client->getKernel());
            $application->setAutoExit(false);

            $application->run(new StringInput('doctrine:schema:drop --force --quiet'));
            $application->run(new StringInput('doctrine:schema:update --force --quiet'));
            $application->run(new StringInput('doctrine:fixtures:load --no-interaction --quiet'));
        }
    }

    /**
     * Creates a Client
     *
     * @param array $options          An array of options to pass to the createKernel class
     * @param array $server           An array of server parameters
     * @param bool  $noAuthentication Authenticate user or not ?
     *
     * @return Client A Client instance
     */
    protected static function createClient(array $options = array(), array $server = array(), $noAuthentication = false)
    {
        // Setup authentication options
        if (!$noAuthentication) {
            if(!isset($server['PHP_AUTH_USER'])) $server['PHP_AUTH_USER'] = static::$adminUsername;
            if(!isset($server['PHP_AUTH_PW']))   $server['PHP_AUTH_PW']   = static::$adminPasswd;
        }

        // Create the client using parent function
        $client = parent::createClient($options, $server);

        // Set the database connection of the test client with the same used in the test
        // NB: In the hypothesis that this function is used in all tests to get the Client
        $client->setConnection($client->getContainer()->get('doctrine')->getConnection());

        return $client;
    }

    /**
     * Create an authenticated client with ROLE_ADMIN
     * @return Client
     */
    protected static function createAdminClient()
    {
        return static::createClient(array(), array(
            'PHP_AUTH_USER' => static::$userUsername,
            'PHP_AUTH_PW'   => static::$userPasswd
        ));
    }

    /**
     * Create an authenticated client with ROLE_USER
     * @return Client
     */
    protected static function createUserClient()
    {
        return static::createClient(array(), array(
            'PHP_AUTH_USER' => static::$userUsername,
            'PHP_AUTH_PW'   => static::$userPasswd
        ));
    }
}
