<?php

namespace Act\ResourceBundle\Services\Export;

use Act\ResourceBundle\Entity\Project;
use Exporter\Source\DoctrineDBALConnectionSourceIterator;
use Act\ResourceBundle\Services\Exporter\CustomHandler;
use Act\ResourceBundle\Services\Exporter\CustomXlsWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectExport
{
    private $em;
    private $container;

    public function __construct($em, $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    /**
     * Export one project to Excel file
     *
     * @param Project   $project
     * @param \DateTime $date    restrict the export by task date
     *
     * @return string
     */
    public function export(Project $project, \DateTime $date = null)
    {
        return $this->generateExcelFile(array($project), $date);
    }

    /**
     * Export one or more projects to Excel file
     *
     * @param array     $projects
     * @param \DateTime $date     restrict the export by task date
     *
     * @return string
     */
    public function exportMultiple(array $projects, \DateTime $date = null)
    {
        return $this->generateExcelFile($projects, $date);
    }

    /**
     * Generates the Excel file with the project export data
     *
     * @param array     $projects
     * @param \DateTime $date     restrict the export by task date
     *
     * @return string
     */
    private function generateExcelFile(array $projects, \DateTime $date = null)
    {
       $doctrineDatabaseConnection = $this->container->get('database_connection');

        // Construct query.
        $sqlQuery = 'SELECT project.id, project.name FROM project WHERE';
        $row = count($projects);
        foreach($projects as $project) {
            if ($row === 1) {
                $sqlQuery = $sqlQuery . ' project.name="' . $project->getName() . '"';
            } else {
                $sqlQuery = $sqlQuery . ' project.name="' . $project->getName() . '" OR';
            }
            $row--;
        }

        // Preparing Source Data Iterator via DoctrineDBALConnectionIterator.
        $sourceIterator = new DoctrineDBALConnectionSourceIterator($doctrineDatabaseConnection, $sqlQuery);

        // Format, Content type and writer for XLS example (change this for CSV, JSON or XML).
        $format = 'xls';
        $contentType = 'application/vnd.ms-excel';

        // Use a custom writer.
        $writer = new CustomXlsWriter('php://output');

        // Name of the final XLS file.
        $filename = sprintf(
            'projects_export_xls_%s_' . time() . '.%s',
            date('Y_m_d', strtotime('now')),
            $format
        );

        // Use different callback if date exist.
        if ($date === null) {
            // Export the data using anonymous function for the streamed response.
            $callback = function() use ($sourceIterator, $writer) {
                CustomHandler::create($sourceIterator, $writer, $this->container)->export();
            };
        } else {
            // Export the data using anonymous function for the streamed response.
            $callback = function() use ($sourceIterator, $writer, $date) {
                CustomHandler::create($sourceIterator, $writer, $this->container)->exportWithDate($date);
            };
        }

        // Using streamed response.
        return new StreamedResponse($callback, 200, array(
            'Content-Type'        => $contentType,
            'Content-Disposition' => sprintf('attachment; filename=%s', $filename)
        ));
    }
}
