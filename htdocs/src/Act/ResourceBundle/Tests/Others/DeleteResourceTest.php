<?php

namespace Act\ResourceBundle\Tests\Others;

use Act\MainBundle\Tests\IsolatedTestCase;

/**
 * Class DeleteResourceTest
 *
 * Test the deletion of a resource
 * to ensure that foreign key and entities
 * doesn't block this deletion.
 *
 * @package Act\ResourceBundle\Tests\Others
 */
class DeleteResourceTest extends IsolatedTestCase
{
    /**
     * Test the deletion of a resource
     */
    public function testDeleteResource()
    {
        // Load the Resource Développeur 3 object
        $resource = $this->em->getRepository('ActResourceBundle:Resource')->findOneBy(array('name' => 'Développeur 3'));
        $this->assertNotNull($resource);

        $router = $this->client->getContainer()->get('router');
        $deleteRoute = $router->generate('admin_act_resource_resource_delete', array('id' => $resource->getId()));
        $listRoute = $router->generate('admin_act_resource_resource_list');

        /**
         * Delete the resource
         */
        $crawler = $this->client->request('GET', $deleteRoute);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        /**
         * Check if there is the button
         */
        $this->assertCount(1, $crawler->filter('.sonata-ba-delete form button'));

        /**
         * Select the delete button and submit form
         */
        $buttonCrawlerNode = $crawler->selectButton('Oui, supprimer');
        $form = $buttonCrawlerNode->form();
        $this->client->submit($form);

        /**
         * Check if the response is a redirect
         */
        $this->assertTrue($this->client->getResponse()->isRedirect($listRoute));
        $crawler = $this->client->followRedirect();

        /**
         * Check if the resource is deleted
         */
        $this->assertCount(0, $crawler->filter('.resource-name:contains("'.$resource->getName().'")'), 'The resource was not deleted - maybe a new relation to cascade ?');
    }
}
