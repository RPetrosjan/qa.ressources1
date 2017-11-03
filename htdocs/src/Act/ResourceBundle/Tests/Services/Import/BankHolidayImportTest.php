<?php

namespace Act\ResourceBundle\Tests\Services\Import;

use Act\MainBundle\Tests\CustomTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BankHolidayImportTest extends CustomTestCase
{
    public function testImport()
    {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        // Go to the sonata admin import page
        $route = $client->getContainer()->get('router')->generate('admin_act_resource_bankholiday_import');
        $crawler = $client->request('GET', $route);

        /**
         * Check if there is the import form
         */
        $this->assertCount(1, $crawler->filter('.panel-body form'));

        // Copy of the file to upload, because the it is deleted after import
        $path = $client->getKernel()->locateResource('@ActResourceBundle/Resources/test/bankholidayImportTest.xlsx');

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
        $bk = $em->getRepository('ActResourceBundle:BankHoliday')->findOneBy(array('name' => 'Test Bankholiday 1'));
        $this->assertNotNull($bk);

        // Remove the project
        $em->remove($bk);
        $em->flush();

        /**
         * Check that the location was created
         */
        $loc = $em->getRepository('ActResourceBundle:Location')->findOneBy(array('name' => 'Test Location'));
        $this->assertNotNull($loc);

        // Remove the project
        $em->remove($loc);
        $em->flush();

        /**
         * Assert database is cleaned of this project
         */
        $this->assertNull($em->getRepository('ActResourceBundle:BankHoliday')->findOneBy(array('name' => 'Test Bankholiday 1')));

        /**
         * Assert database is cleaned of this location
         */
        $this->assertNull($em->getRepository('ActResourceBundle:Location')->findOneBy(array('name' => 'Test Location')));
    }
}
