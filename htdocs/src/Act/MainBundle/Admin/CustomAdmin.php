<?php

namespace Act\MainBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;

/**
 * Class CustomAdmin
 *
 * Extends the sonata abstract admin class
 * to add some custom logic, but still abstract
 *
 * @package Act\MainBundle\Admin
 */
abstract class CustomAdmin extends Admin
{
    /**
     * {@inheritdoc}
     */
    public function isAclEnabled()
    {
        return false; // remove all ACL buttons
    }
}