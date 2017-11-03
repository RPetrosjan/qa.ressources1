<?php

namespace Act\LdapBundle\DependencyInjection\Security\Factory;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

/**
 * Class LdapFactory
 *
 * To add our custom authentication listener and provider
 * into the symfony security, we must use a Factory.
 *
 * We also declare some configuration to be allowed under
 * this factory key in the security configuration file.
 *
 */
class LdapFactory implements SecurityFactoryInterface
{
    /**
     * Adds the LdapListener and the LdapProvider to the container, for the right security context
     *
     * @param ContainerBuilder $container
     * @param $id the name of the firewall
     * @param $config the config data of this factory (defined in addConfiguration())
     * @param $userProvider the name of the user provider service
     * @param $defaultEntryPoint
     *
     * @return array
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        // Adds the LdapProvider to the container
        $providerId = 'security.authentication.provider.ldap.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('ldap.security.authentication.provider'))
            ->replaceArgument(1, new Reference($userProvider))                                                  // Replace with a reference to the user provider object
            ->replaceArgument(2, $config['server'])                                                             // Replace with the server config
            ->replaceArgument(3, $config['port'])                                                               // Replace with the port config
            ->replaceArgument(4, $config['login_prefix'])                                                       // Replace with the login_prefix config
            ->replaceArgument(5, $config['base_dn'])                                                            // Replace with the base_dn config
            ->replaceArgument(6, $config['user_field'])                                                         // Replace with the user_field config
        ;

        // Adds the LdapListener to the container
        $listenerId = 'security.authentication.listener.ldap.'.$id;
        $container
            ->setDefinition($listenerId, new DefinitionDecorator('ldap.security.authentication.listener'))
        ;

        // Return the provider + the listener for the given firewall
        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    /**
     * Define the time when the provider is called
     * Must be either : pre_auth, form, http, remember_me
     *
     * @return string
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    /**
     * Define the configuration key used to reference the provider
     *
     * @return string
     */
    public function getKey()
    {
        return 'ldap';
    }

    /**
     * Adds custom configurations into the security configuration file, under this provider key
     *
     * @param NodeDefinition $node
     */
    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('server')
                    ->defaultValue('193.252.202.23')
                ->end()
                ->integerNode('port')
                    ->defaultValue(389)
                ->end()
                ->scalarNode('login_prefix')
                    ->defaultValue('Actency\\')
                ->end()
                ->scalarNode('base_dn')
                    ->defaultValue('OU=SBSUsers,OU=Users,OU=MyBusiness,DC=actency,DC=local')
                ->end()
                ->scalarNode('user_field')
                    ->defaultValue('sAMAccountName')
                ->end()
            ->end()
        ;
    }
}
