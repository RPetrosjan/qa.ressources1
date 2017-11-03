<?php

namespace Act\MainBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Client as BaseClient;
use Doctrine\DBAL\Connection;

/**
 * Test Client
 *
 * Extends the default Client class to keep the same connection
 * to the database during different requests done in tests
 */
class Client extends BaseClient
{
    /**
     * @var Connection the database connection
     *
     * This connection must be the same as used in the test
     * to be able to rollback all changes to the database
     * done during the test and its requests
     */
    protected $connection;

    /**
     * @var boolean was there a request to shutdown ?
     */
    protected $requested;

    /**
     * Makes a request.
     *
     * @param object $request An origin request instance
     *
     * @throws \Exception if connection not set
     *
     * @return object An origin response instance
     */
    protected function doRequest($request)
    {
        if ($this->requested) {
            // If there was a previous request
            // Shutdown and then reboot the kernel
            $this->kernel->shutdown();
            $this->kernel->boot();
        }

        // Memorize that we need to shutdown and reboot
        $this->requested = true;

        if ($this->connection == null) {
            throw new \Exception('Please set the connection of the test client object');
        }

        // Set the defined connection
        $this->getContainer()->set('doctrine.dbal.default_connection', $this->connection);

        // Handle request
        return $this->kernel->handle($request);
    }

    /**
     * Returns the database connection
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set the database connection
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
}
