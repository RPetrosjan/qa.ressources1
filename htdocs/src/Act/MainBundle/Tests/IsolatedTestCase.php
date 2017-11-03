<?php

namespace Act\MainBundle\Tests;

/**
 * Parent class for tests that need database
 * isolation to be done automatically.
 */
abstract class IsolatedTestCase extends CustomTestCase
{
    protected $client;
    protected $em;

    /**
     * Called before every tests
     * - Initializes a new client and entity manager
     * - Starts a new transaction
     */
    public function setUp()
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();
        $this->em->beginTransaction();
    }

    /**
     * Called after every tests
     * - Rollback the transaction
     * - Closes the entity manager
     */
    public function tearDown()
    {
        $this->em->rollback();
        $this->em->close();
    }
}
