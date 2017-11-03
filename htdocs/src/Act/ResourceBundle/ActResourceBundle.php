<?php

namespace Act\ResourceBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * This bundle contains all the code necessary
 * for the Act&Resources application to work.
 *
 */
class ActResourceBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        //return 'FOSUserBundle';
    }
}
