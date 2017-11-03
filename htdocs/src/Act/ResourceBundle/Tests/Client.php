<?php

namespace Act\ResourceBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Client as BaseClient;

/**
 * Class Client
 *
 * Extends the default Client class to keep the same connection
 * to the database during different requests done in tests
 */
class Client extends BaseClient
{
    /**
     * @var Connection the database connection
     */
    protected static $connection;

    /**
     * @var Request the request
     */
    protected $requested;

    /**
     * Makes a request.
     *
     * @param object $request An origin request instance
     *
     * @return object An origin response instance
     */
    protected function doRequest($request)
    {
        if ($this->requested) {
            $this->kernel->shutdown();
            $this->kernel->boot();
        }

        $this->requested = true;

        if (null === self::$connection) {
            self::$connection = $this->getContainer()->get('doctrine.dbal.default_connection');
        } else {
            $this->getContainer()->set('doctrine.dbal.default_connection', self::$connection);
        }

        return $this->kernel->handle($request);
    }

    /**
     * Returns the database connection
     * @return Connection
     */
    public function getConnection()
    {
        return self::$connection;
    }
}
