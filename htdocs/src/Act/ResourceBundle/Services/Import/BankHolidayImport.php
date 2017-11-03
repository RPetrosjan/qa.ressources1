<?php

namespace Act\ResourceBundle\Services\Import;

use Act\ResourceBundle\Entity\BankHoliday;
use Act\ResourceBundle\Entity\Location;

/**
 * Class BankHolidayImport
 *
 * Manages the import of bankholidays from an excel file
 */
class BankHolidayImport extends Import
{
    /**
     * Parse the Excel file and retrieve bankholidays
     *
     * @param $filename
     * @return array
     * @throws \Exception
     */
    public function import($filename)
    {
        $imported = array();

        // Get the column mapping
        $data = $this->getData($filename);

        foreach ($data as $row) {
            $bankholiday = new BankHoliday();
            $bankholiday->setName($row['name']);
            $bankholiday->setStart($row['date']);

            $locations = explode(';', $row['locations']);
            foreach ($locations as $name) {
                $name = trim($name);
                $location = $this->em->getRepository('ActResourceBundle:Location')->findOneBy(array('name' => $name));
                if (!$location) {
                    // Create location if not found
                    $location = new Location();
                    $location->setName($name);

                    $this->em->persist($location);
                    $imported[] = $location;
                }

                $bankholiday->addLocation($location);
            }

            $this->em->persist($bankholiday);
            $imported[] = $bankholiday;
        }

        // Save all new bankholidays and locations
        $this->em->flush();

        return $imported;
    }

    protected function getRequiredColumns()
    {
        return array(
          'date' => 'date',
          'name' => array('nom', 'name'),
          'locations' => array('lieux', 'locations')
        );
    }

    protected function getTypes()
    {
        return array(
          'date' => 'date',
          'name' => 'string',
          'locations' => 'string'
        );
    }
}
