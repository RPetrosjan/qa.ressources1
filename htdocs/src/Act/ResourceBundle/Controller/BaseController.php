<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * BaseController
 *
 * Contains the code to display the homepage
 * or user dashboard and also the ldap login page.
 *
 */
class BaseController extends Controller
{
    /**
     * Display the homepage - user dashboard
     */
    public function indexAction()
    {
        $user = $this->getUser();
        $dates = $this->get('act_main.date.manager')->findFirstAndLastDaysOfWeek();

        // Generate week projects data.
        $weekProjects = $this->get('act_resource.weekly_projects_manager')->getWeekProjects($dates['start'], $dates['end']);

        // Generate week planning data.
        $planningData = $this->get('act_resource.week_planning_manager')->getWeekPlanning($user->getResource());

        // Get all resources
        $resources = $this->get('doctrine')->getManager()->getRepository('ActResourceBundle:Resource')->findAll();
        $teams = array();
        foreach ($resources as $resource) {
            if (!isset($teams[$resource->getTeam()->getId()])) {
                $teams[$resource->getTeam()->getId()] = array(
                    'team' => $resource->getTeam(),
                    'resources' => array()
                );
            }

            $teams[$resource->getTeam()->getId()]['resources'][$resource->getId()] = $resource;
        }

        // Show view
        return $this->render('ActResourceBundle:Base:index.html.twig', array(
            'planning' => $planningData,
            'projects' => $weekProjects,
            'resources' => $resources,
            'teams' => $teams
        ));
    }

    /**
     * Display the project details with ajax
     */
    public function ajaxAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new \Exception('Only AJAX allowed');
        }

        // Get dates.
        $days = $request->get('days');
        $date = new \DateTime('now');
        $interval = new \DateInterval('P' . abs($days) . 'D');
        if ($days < 0) {
            $date->sub($interval);
        } else {
            $date->add($interval);
        }
        $dates = $this->get('act_main.date.manager')->findFirstAndLastDaysOfWeek($date);

        // Get project.
        $project = $this->getDoctrine()->getRepository('ActResourceBundle:Project')->find($request->get('project'));

        // Get data.
        $planning = $this->get('act_resource.weekly_projects_manager')->getWeekPlanning($project, $dates['start'], $dates['end']);

        return $this->render('ActResourceBundle:Base:ajax.html.twig', array(
            'planning' => $planning,
        ));
    }
}
