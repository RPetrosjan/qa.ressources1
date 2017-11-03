<?php

namespace Act\ResourceBundle\Tests;

/**
 * Interface TypeTestCaseInterface
 */
interface TypeTestCaseInterface
{
    /**
     * Determine if the client must be authentified or not
     * @return boolean
     */
    public function mustAuthentify();

    /**
     * Determine if the client must reset the database and fixtures
     * to it's original state
     *
     * Must return true if the test is bringing unreverted changes to the database
     *
     * If possible, revert changes by hand or with the use of transactions to
     * speed up tests.
     *
     * @return boolean
     */
    public function mustResetDatabase();
}
