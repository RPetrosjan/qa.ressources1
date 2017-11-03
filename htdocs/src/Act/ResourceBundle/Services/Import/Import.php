<?php

namespace Act\ResourceBundle\Services\Import;

use Doctrine\ORM\EntityManagerInterface;
use Liuggio\ExcelBundle\Factory;

/**
 * Class Import
 *
 * Abstract class to extend by import services
 * @author Renrhaf
 */
abstract class Import
{
    protected $em;
    protected $excel;

    /**
     * Inject the entity manager
     * @param EntityManagerInterface $em
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Inject the excel factory
     * @param Factory $excel
     */
    public function setExcelObject(Factory $excel)
    {
        $this->excel = $excel;
    }

    /**
     * Parse the Excel file and persist objects
     *
     * @param  string     $filename the path to the excel file
     * @return array      the list of imported objects
     * @throws \Exception in case something went wrong
     */
    abstract public function import($filename);

    /**
     * Return an array of required columns in the format :
     * column_key_name => array('Possible column name 1', 'Possible column name 2', ...)
     *
     * @return array
     */
    abstract protected function getRequiredColumns();

    /**
     * Returns an array of data types in the format :
     * column_key_name => 'format_name'
     *
     * Possible formats : 'date', 'string, ...
     *
     * @return array
     */
    abstract protected function getTypes();

    /**
     * Extract data from the excel file
     *
     * @param  string $filename     the path to the excel file
     * @param  int    $firstDataRow
     * @return array
     */
    protected function getData($filename, $firstDataRow = 2)
    {
        $data = array();

        $excelObj = $this->excel->createPHPExcelObject($filename);
        $excelObj->setActiveSheetIndex(0);

        // Get the mapping
        $mapping = $this->getColumnsMapping($excelObj);

        $rowIterator = $excelObj->getActiveSheet()->getRowIterator($firstDataRow);
        foreach ($rowIterator as $row) {
            $rowIndex = $row->getRowIndex() - 1;
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);

            foreach ($cellIterator as $cell) {
                $colIndex = \PHPExcel_Cell::columnIndexFromString($cell->getColumn());

                // Check if the cell is useful, according to the mapping
                if (!isset($mapping[$colIndex-1])) {
                    // Get the last useful column index
                    end($mapping);
                    $last_key = key($mapping);
                    if ($colIndex > $last_key) {
                        // If we passed it, leave the foreach
                        break;
                    } else {
                        // Else continue to next cell
                        continue;
                    }
                }

                $value = null;
                $column = $mapping[$colIndex - 1];
                $type = $this->getType($column);

                if ($type == 'date') {
                    /* DATE */
                    if (preg_match('#^\w{3}\s(\d{2}/\d{2}/\d{2})$#', $cell->getValue(), $matches)) {
                        /* Date at format "Lun 11/07/2014" - Excel can't parse it properly */
                        $value = \DateTime::createFromFormat('d/m/y', $matches[1]);
                    } else {
                        $value = \PHPExcel_Shared_Date::ExcelToPHPObject($cell->getValue());
                    }

                    $value->setTime(0, 0, 0);
                } elseif ($type == 'float') {
                    /* FLOAT */
                    $value = (float) str_replace(',', '.', substr($cell->getValue(), 0));
                } else {
                    /* STRING */
                    if (mb_detect_encoding($cell->getValue()) == 'UTF-8') {
                        // Check for any unwanted special chars
                        $iso8859 = iconv("UTF-8", "ISO-8859-1", $cell->getValue());
                        $iso8859 = str_replace("\xA0", " ", $iso8859);

                        // Convert back to UTF-8
                        $value = iconv("ISO-8859-1", "UTF-8", $iso8859);
                    } else {
                        $value = $cell->getValue();
                    }
                }

                $data[$row->getRowIndex() - 1][$column] = $value;
            }
        }

        return $data;
    }

    /**
     * Extract the columns mapping from the excel file
     * using the mapping defined in 'getRequiredColumns' function
     *
     * @param  \PHPExcel  $excelObj
     * @param  int        $headerRow
     * @return array
     * @throws \Exception
     */
    protected function getColumnsMapping(\PHPExcel $excelObj, $headerRow = 1)
    {
        $mapping = array();
        $column = 0;

        // Get all of the first row cells name
        while (($foundName = $excelObj->getActiveSheet()->getCellByColumnAndRow($column, $headerRow)->getValue()) != null) {
            $foundName = strtolower($foundName);

            foreach ($this->getRequiredColumns() as $key => $names) {
                if (!is_array($names)) {
                    $names = array($names);
                }

                foreach ($names as $name) {
                    $distance = levenshtein($foundName, $name);
                    if ($distance >= 0 && $distance < 2) {
                        $mapping[$column] = $key;
                    }
                }
            }

            $column++;
        }

        // Check if we found all required columns
        foreach ($this->getRequiredColumns() as $key => $names) {
            $found = false;
            foreach ($mapping as $col) {
                if ($col == $key) {
                    $found = true;
                }
            }

            if (!$found) {
                throw new \Exception('Impossible de trouver la colonne "'.join('" ou "', $names).'"');
            }
        }

        return $mapping;
    }

    /**
     * Get the type of the given column
     *
     * @param  string     $column the column key name
     * @return string
     * @throws \Exception
     */
    protected function getType($column)
    {
        $types = $this->getTypes();

        if (!isset($types[$column])) {
            throw new \Exception('No column "'.$column.'" was found !');
        }

        return $types[$column];
    }
}
