<?php

namespace Act\ResourceBundle\Tests\Services\Export;

use Act\MainBundle\Tests\IsolatedTestCase;

/**
 * Class ExportResourceUsageExportTest
 *
 * Tests for ResourceUsageExport
 *
 * @package Act\ResourceBundle\Tests\Services\Export
 */
class ResourceUsageExportTest extends IsolatedTestCase
{
    /**
     * Testing the resource usage export
     */
    public function testResourceUsageExport()
    {
        // Prepare post values
        $resources = array(1);
        $tags = array('typeHoliday');
        $projects = array(1);
        $start = '01/01/2014';
        $end = '30/01/2014';

        // Generate route to the resource usage export
        $route = $this->client->getContainer()->get('router')->generate('act_resource_resource_usage_export_excel');
        $this->client->request('POST', $route, array(
            'start' => $start,
            'end' => $end,
            'resources' => $resources,
            'tags' => $tags,
            'projects' => $projects,
        ));

        /**
         * Check the request has success
         */
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Create a temporary excel file with the content of the request
        $filename = tempnam(sys_get_temp_dir(), 'test-resource-usage-export.xlsx');
        $handle = fopen($filename, 'w');
        fwrite($handle, $this->client->getResponse()->getContent());
        fclose($handle);

        // Excel reader
        $phpExcel = $this->client->getContainer()->get('phpexcel');
        $excelObj = $phpExcel->createPHPExcelObject($filename);

        /**
         * Check first line
         */

        $firstline = 'Taux d\'utilisation des ressources - 01/01/2014 - 30/01/2014';
        $this->assertEquals($firstline, $excelObj->getActiveSheet()->getCellByColumnAndRow(0, 1)->getValue());

        /**
         * Checks all the data
         */
        $weeks = array('W01/2014', 'W02/2014', 'W03/2014', 'W04/2014', 'W05/2014');
        for ($i = 0; $i < count($weeks); $i++) {
            $this->assertEquals($weeks[$i], $excelObj->getActiveSheet()->getCellByColumnAndRow($i + 1, 2)->getValue());
        }

        $resources = array('DEV1', 'S. Tech', 'Total');
        for ($i = 0; $i < count($resources); $i++) {
            $this->assertEquals($resources[$i], $excelObj->getActiveSheet()->getCellByColumnAndRow(0, $i + 3)->getValue());
        }

        $resourcesusage = array('40', '60', '0', '0', '0');
        for ($i = 0; $i < count($resourcesusage); $i++) {
            $this->assertEquals($resourcesusage[$i], $excelObj->getActiveSheet()->getCellByColumnAndRow($i + 1, 3)->getValue());
        }
        for ($i = 0; $i < count($resourcesusage); $i++) {
            $this->assertEquals($resourcesusage[$i], $excelObj->getActiveSheet()->getCellByColumnAndRow($i + 1, 4)->getValue());
        }
        for ($i = 0; $i < count($resourcesusage); $i++) {
            $this->assertEquals($resourcesusage[$i], $excelObj->getActiveSheet()->getCellByColumnAndRow($i + 1, 5)->getValue());
        }

        unlink($filename);
    }
}
