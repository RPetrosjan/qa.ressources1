<?php

namespace Act\ResourceBundle\Tests\Others;

use Act\MainBundle\Tests\IsolatedTestCase;

/**
 * Class DeleteProjectTest
 *
 * Test the deletion of a project
 * to ensure that foreign key and entities
 * doesn't block this deletion.
 *
 * @package Act\ResourceBundle\Tests\Others
 */
class DeleteProjectTest extends IsolatedTestCase
{
    /**
     * Test the deletion of a project
     */
    public function testDeleteProject()
    {
        // Load the Project 1 object
        $project = $this->em->getRepository('ActResourceBundle:Project')->findOneBy(array('name' => 'Projet 1'));
        $this->assertNotNull($project);

        $router = $this->client->getContainer()->get('router');
        $deleteRoute = $router->generate('admin_act_resource_project_delete', array('id' => $project->getId()));
        $homeRoute = $router->generate('act_resource_home');
        $redirectRoute = $router->generate('admin_act_resource_project_list');

        /**
         * Check there is a prefered project
         */
        $crawler = $this->client->request('GET', $homeRoute);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertCount(1, $crawler->filter('.prefered-project:contains("'.$project->getName().'")'));

        /**
         * Now, we delete the project
         */
        $crawler = $this->client->request('GET', $deleteRoute);
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        /**
         * Check if there is the delete button
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
        $this->assertTrue($this->client->getResponse()->isRedirect($redirectRoute));
        $crawler = $this->client->followRedirect();

        /**
         * Check if there is the project in the list
         */
        $this->assertCount(0, $crawler->filter('.project-name:contains("'.$project->getName().'")'), 'The project was not deleted - maybe a new relation to cascade ?');

        /**
         * Check that the prefered project is deleted too
         */
        $crawler = $this->client->request('GET', $homeRoute);
        $this->assertCount(0, $crawler->filter('.prefered-project'));
    }
}
