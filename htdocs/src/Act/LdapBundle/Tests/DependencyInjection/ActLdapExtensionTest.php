<?php

namespace Act\LdapBundle\Tests\DependencyInjection;

use Act\LdapBundle\DependencyInjection\ActLdapExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ActLdapExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerBuilder */
    public $container;

    public function testConfigurationNamespace()
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new ActLdapExtension());
        $this->assertTrue($container->hasExtension('act_ldap'));
    }

    public function testLoadDefaultConfiguration()
    {
        $container = new ContainerBuilder();
        $extension = new ActLdapExtension();

        $extension->load(array(), $container);

        $this->assertTrue($container->hasDefinition('ldap.security.authentication.provider'));
        $this->assertTrue($container->hasDefinition('ldap.security.authentication.listener'));
    }
}
