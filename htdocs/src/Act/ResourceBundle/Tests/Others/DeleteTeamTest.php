<?php

namespace Act\ResourceBundle\Tests\Others;

use Act\MainBundle\Tests\IsolatedTestCase;

/**
 * Class DeleteTeamTest
 *
 * Test the deletion of a team
 * to ensure that foreign key and entities
 * doesn't block this deletion.
 *
 * @package Act\ResourceBundle\Tests\Others
 */
class DeleteTeamTest extends IsolatedTestCase
{
    /**
     * Test the deletion of a resource
     */
    public function testDeleteTeam()
    {
        // Load the Team S. Tech
        $team = $this->em->getRepository('ActResourceBundle:Resource')->find(1);
        $this->assertNotNull($team);

        $router = $this->client->getContainer()->get('router');
        $deleteRoute = $router->generate('admin_act_resource_team_delete', array('id' => $team->getId()));
        $listRoute = $router->generate('admin_act_resource_team_list');

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
        $this->assertCount(0, $crawler->filter('.team-name:contains("'.$team->getName().'")'), 'The resource was not deleted - maybe a new relation to cascade ?');
    }
}
