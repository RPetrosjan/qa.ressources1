<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Act\ResourceBundle\Entity\Project;

/**
 * ExportController
 *
 * Manage the export of data from the application
 */
class ExportController extends ContainerAware
{
    /**
     * Export previsional assignments to an Excel file
     *
     * @return Excel file to download
     */
    public function previsionalExportAction()
    {
        return $this->container->get('act_resource.previsional_assignments.export')->export();
    }

    /**
     * Export project tasks to an Excel file
     *
     * @param Project $project the project to export
     *
     * @return Excel file to download
     */
    public function projectExportAction(Project $project)
    {
        // Generate the file content
        $content = $this->container->get('act_resource.project_export')->export($project);
        $filename = $project->getNameShort() . '-export';

        // Get the referer page
        $referer = $this->container->get('request')->query->get('referer');
        if ($referer == null) {
            $referer = $this->container->get('router')->generate('act_resource_project_show', array('id' => $project->getId()));
        }

        // If there was a problem...
        if ($content == null || strlen($content) == 0) {
            $this->container->get('session')->getFlashBag()->add('warning', $this->container->get('translator')->trans('export.project.error'));

            return new RedirectResponse($referer, 302);
        }

        // Return the file
        return $content;
    }
}
