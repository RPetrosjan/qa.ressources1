<?php

namespace Act\ResourceBundle\Tests\Services;

use Act\MainBundle\Tests\CustomTestCase;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * Tests relative to all Sonata Admin pages.
 * Check if all lists are still working.
 */
class AdminTest extends CustomTestCase
{
    /**
     * Check that all admin lists are working properly.
     */
    public function testLists()
    {
        $client = static::createClient();

        // Get all admin classes
        $pool = $client->getContainer()->get('sonata.admin.pool');
        $admins = $pool->getAdminClasses();
        foreach ($admins as $class => $admin) {
            $admin = $client->getContainer()->get(array_shift($admin));

            // Get all routes for this admin
            $routes = $admin->getRoutes();
            foreach ($routes->getElements() as $name => $route) {
                // Only keep list actions
                if (strpos($name, 'list') !== false) {
                    // Generate path
                    try {
                        $path = $client->getContainer()->get('router')->generate($route->getDefaults()['_sonata_name']);
                    } catch(MissingMandatoryParametersException $exception) {
                        $path = $client->getContainer()->get('router')->generate($route->getDefaults()['_sonata_name'], array('id' => 1));
                    }

                    /**
                     * Check if the response is OK
                     */
                    $client->request('GET', $path);
                    $this->assertTrue($client->getResponse()->isSuccessful(), 'Admin list ' . $class . ' has an error.');
                }
            }
        }
    }
}
