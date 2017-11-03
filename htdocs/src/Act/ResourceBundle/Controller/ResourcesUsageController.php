<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Act\ResourceBundle\Entity\Project;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class ResourcesUsageController
 *
 * Controller to display a configurable dashboard
 * about team and resources usage for a date period
 *
 * @package Act\ResourceBundle\Controller
 */
class ResourcesUsageController extends Controller
{
    /**
     * Display the form to configure the resource usage page
     *
     * @return Response
     */
    public function indexAction()
    {
        $form = array(
          'ed' => null,
          'st' => null,
          're' => null,
          'pj' => null,
          'tg' => null
        );

        $em = $this->getDoctrine()->getManager();

        return $this->render(
          'ActResourceBundle:ResourcesUsage:resource_usage_form.html.twig',
          array(
            'teams' => $em->getRepository('ActResourceBundle:Team')->findAll(),
            'tags' => $this->getTags(),
            'projects' => $em->getRepository('ActResourceBundle:Project')->findAll(),
            'form' => $form,
          )
        );
    }

    /**
     * Generates the resource usage dashboard
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    public function showAction(Request $request)
    {
        $start = $request->request->get('start');
        $end = $request->request->get('end');
        $resources = $request->request->get('resources');
        $tags = $request->request->get('tags');
        $projects = $request->request->get('projects');

        // Check dates
        $dates = $this->checkDates($start, $end);
        if (count($dates) == 0) {
            $this->container->get('session')->getFlashBag()->add('error', $this->container->get('translator')->trans('invalid.date.interval'));

            return $this->returnFormData($start, $end, $resources, $projects, $tags);
        }

        // Check resources
        if (is_null($resources) || count($resources) == 0) {
            $this->container->get('session')->getFlashBag()->add('error', $this->container->get('translator')->trans('select.at.least.one.resource'));

            return $this->returnFormData($start, $end, $resources, $projects, $tags);
        }

        // Check if export asked
        if ($request->request->has('export-excel')) {
            return $this->resourceUsageExportExcelAction($request);
        }

        // Generate data response
        $response = $this->container->get('act_resource.resources_usage_manager')->generateUsageData($resources, $dates['start'], $dates['end'], $tags, $projects);

        // If some projets are specified, load them
        if (count($projects) > 0) {
            $projects = $this->get('doctrine')->getManager()->getRepository('ActResourceBundle:Project')->findBy(array('id' => $projects));
        }

        return $this->render(
          'ActResourceBundle:ResourcesUsage:resource_usage.html.twig',
          array(
            'start' => $start,
            'end' => $end,
            'tags' => $tags,
            'projects' => $projects,
            'resources' => $resources,
            'reponse' => $response
          )
        );
    }

    /**
     * Export the resource usage dashboard in an Excel file.
     *
     * @Route("/resource/usage/export/excel", name="act_resource_resource_usage_export_excel")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function resourceUsageExportExcelAction(Request $request)
    {
        $start = $request->request->get('start');
        $end = $request->request->get('end');
        $resources = $request->request->get('resources');
        $tags = $request->request->get('tags');
        $projects = $request->request->get('projects');

        // Check dates
        $dates = $this->checkDates($start, $end);
        if (count($dates) == 0) {
            $this->container->get('session')->getFlashBag()->add('error', $this->container->get('translator')->trans('invalid.date.interval'));

            return $this->returnFormData($start, $end, $resources, $projects, $tags);
        }

        // Check resources
        if (is_null($resources) || count($resources) == 0) {
            $this->container->get('session')->getFlashBag()->add('error', $this->container->get('translator')->trans('select.at.least.one.resource'));

            return $this->returnFormData($start, $end, $resources, $projects, $tags);
        }

        return $this->container->get('act_resource.resources_usage_export')->exportExcel(
          $dates['start'],
          $dates['end'],
          $resources,
          $tags,
          $projects
        );
    }

    /**
     * Helper function to check given dates.
     *
     * @param $start
     * @param $end
     *
     * @return array
     */
    private function checkDates($start, $end)
    {
        $dates = array();

        if (preg_match('/(\d{2})|(\d{2})|(\d{4})/', $start) && preg_match('/(\d{2})|(\d{2})|(\d{4})/', $end)) {
            $start = \DateTime::createFromFormat('d/m/Y', $start);
            $end = \DateTime::createFromFormat('d/m/Y', $end);

            if ($start != null && $end != null && $start < $end) {
                $dates['start'] = $start;
                $dates['end'] = $end;
            }
        }

        return $dates;
    }

    private function returnFormData($start, $end, $resources, $projects, $tag)
    {
        $em = $this->getDoctrine()->getManager();
        $form = array(
          'ed' => $end,
          'st' => $start,
          're' => $resources,
          'pj' => $projects,
          'tg' => $tag
        );

        return $this->render(
          'ActResourceBundle:ResourcesUsage:resource_usage_form.html.twig',
          array(
            'teams' => $em->getRepository('ActResourceBundle:Team')->findAll(),
            'tags' => $this->getTags(),
            'projects' => $em->getRepository('ActResourceBundle:Project')->findAll(),
            'form' => $form
          )
        );
    }

    /**
     * Helper function to return the list of project type tags
     *
     * @TODO this is not maintainable : set this in a service or directly in the entity if possible
     *
     * @return array
     */
    private function getTags()
    {
        return array(
          array('value' => 'typeInternal', 'trans' => 'project.type.internal'),
          array('value' => 'typeHoliday', 'trans' => 'project.type.holiday'),
          array('value' => 'typeSigned', 'trans' => 'project.type.signed'),
          array('value' => 'typePresaleGT70', 'trans' => 'project.type.presale.gt70'),
          array('value' => 'typePresaleLT70', 'trans' => 'project.type.presale.lt70'),
          array('value' => 'typeResearch', 'trans' => 'project.type.research'),
          array('value' => 'typeInactive', 'trans' => 'project.type.deactivate'),
        );
    }

    /**
     * Get the filtered projects list through AJAX..
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getFilteredProjectsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $projects = array();

        // Make sure the request is an AJAX request.
        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedException('Only accessible via AJAX');
        }

        // Extract parameters from URL.
        $start = $request->request->get('start', null);
        $end = $request->request->get('end', null);
        $types = $request->request->get('types', array());
        $resources = $request->request->get('resources', array());

        // Check dates.
        $dates = $this->checkDates($start, $end);
        if (count($dates) == 0) {
            throw $this->createAccessDeniedException('Dates are not valid.');
        }

        // Get filtered projects.
        $results = $em->getRepository('ActResourceBundle:Project')->getProjectsByDatesTypesResources($dates['start'], $dates['end'], $types, $resources);
        foreach ($results as $res) {
            $projects[$res->getId()] = $res->getName();
        }

        // Return JSON.
        return new JsonResponse($projects);
    }
}
