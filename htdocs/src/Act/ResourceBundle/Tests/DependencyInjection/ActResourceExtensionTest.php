<?php

namespace Act\ResourceBundle\Tests\DependencyInjection;

use Act\ResourceBundle\DependencyInjection\ActResourceExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ActResourceExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContainerBuilder */
    public $container;

    public function testConfigurationNamespace()
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new ActResourceExtension());

        // Check extension is loaded
        $this->assertTrue($container->hasExtension('act_resource'));
    }

    public function testLoadDefaultConfiguration()
    {
        $container = new ContainerBuilder();
        $extension = new ActResourceExtension();

        $extension->load(array(), $container);

        // Check service file is loaded
        $this->assertTrue($container->hasDefinition('act_resource.listener.ldap_user_not_found'));
        $this->assertTrue($container->hasDefinition('act_resource.listener.ldap_user_loaded'));

        // Check admin file is loaded
        $this->assertTrue($container->hasDefinition('sonata.admin.project'));
    }
}
