<?php

namespace Application\Sonata\UserBundle\Tests\Others;

use Act\MainBundle\Tests\IsolatedTestCase;

/**
 * Class DeleteUserTest
 *
 * Test the deletion of a user
 * to ensure that the associate resource
 * still exists after.
 *
 * @package Act\ResourceBundle\Tests\Others
 */
class DeleteUserTest extends IsolatedTestCase {

    public function testDeleteUser()
    {
        // Load the user ID 1 object
        $user = $this->em->getRepository('ApplicationSonataUserBundle:User')->findOneBy(array('id' => 1));
        $resourceId = $user->getResource()->getId();
        $this->assertNotNull($user);

        $router = $this->client->getContainer()->get('router');
        $deleteRoute = $router->generate('admin_sonata_user_user_delete', array('id' => $user->getId()));
        $listRoute = $router->generate('admin_sonata_user_user_list');

        /**
         * Check if the user has an assigned resource
         */
        $resources = $this->em->getRepository('ActResourceBundle:Resource')->findOneBy(array('user' => $user->getId()));
        $this->assertNotNull($resources);

        /**
         * Delete the user
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
        $this->client->followRedirect();

        /**
         * Check that the resource is not deleted
         * NB: we use a custom query to bypass caching
         */
        $query = $this->em->createQuery('SELECT r FROM ActResourceBundle:Resource r WHERE r.id = :id');
        $query->setParameter(':id', $resourceId);
        $query->useQueryCache(false);
        $query->useResultCache(false);

        $resource = $query->getOneOrNullResult();
        $this->assertNotNull($resource);
    }
}