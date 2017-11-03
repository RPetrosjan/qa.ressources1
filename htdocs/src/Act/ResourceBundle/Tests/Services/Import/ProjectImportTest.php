<?php

namespace Act\ResourceBundle\Tests\Services\Import;

use Act\MainBundle\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProjectImportTest extends CustomTestCase
{
    /**
     * Test the project import
     */
    public function testImport()
    {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        // Go to the sonata admin import page
        $route = $client->getContainer()->get('router')->generate('admin_act_resource_project_import');
        $crawler = $client->request('GET', $route);

        /**
         * Check if there is the import form
         */
        $this->assertCount(1, $crawler->filter('.panel-body form'));

        // Copy of the file to upload, because the it is deleted after import
        $path = self::$kernel->locateResource('@ActResourceBundle/Resources/test/projectImportTest.xlsx');

        // Prepare the file to upload
        $file = new UploadedFile($path, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', filesize($path), 0, true);

        // Find the login form, fill it and submit it
        $form = $crawler->filter('.panel-body form')->form(array(
            'form[attachment]' => $file
        ));
        $crawler = $client->submit($form);

        /**
         * Check the upload success
         */
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        /**
         * Check the presence of success message
         */
        $this->assertCount(1, $crawler->filter('.alert-success'));

        /**
         * Check that the project was created
         */
        $project = $em->getRepository('ActResourceBundle:Project')->findOneBy(array('name' => 'Test Project'));
        $this->assertNotNull($project);

        // Remove the project
        $em->remove($project);
        $em->flush();

        /**
         * Assert database is cleaned of this project
         */
        $this->assertNull($em->getRepository('ActResourceBundle:Project')->findOneBy(array('name' => 'Test Project')));
    }

    /**
     * Test the project import with at least
     * one missing team/profile specified
     */
    public function testImportFail()
    {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        // Go to the sonata admin import page
        $route = $client->getContainer()->get('router')->generate('admin_act_resource_project_import');
        $crawler = $client->request('GET', $route);

        /**
         * Check if there is the import form
         */
        $this->assertCount(1, $crawler->filter('.panel-body form'));

        // Copy of the file to upload, because the it is deleted after import
        $path = self::$kernel->locateResource('@ActResourceBundle/Resources/test/projectImportTestFail.xlsx');

        // Prepare the file to upload
        $file = new UploadedFile($path, 'test-fail.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', filesize($path), 0, true);

        // Find the login form, fill it and submit it
        $form = $crawler->filter('.panel-body form')->form(array(
            'form[attachment]' => $file
        ));
        $crawler = $client->submit($form);

        /**
         * Check the upload success
         */
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        /**
         * Check the presence of warning message
         */
        $this->assertCount(1, $crawler->filter('.alert-warning'));

        /**
         * Check that the project was not created
         */
        $project = $em->getRepository('ActResourceBundle:Project')->findOneBy(array('name' => 'Test Project'));
        $this->assertNull($project);
    }
}
