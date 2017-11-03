<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * AssignmentController
 *
 * Contains all functions that deals with
 * the management of Assignments in the application
 *
 */
class AssignmentController extends Controller
{
    /**
     * Display the previsional assignments for a given week and year.
     * The page can show one or more teams, and can be restricted by projects.
     *
     * By default, it will show the current week and every assignments.
     *
     * @return Response
     */
    public function previsionalAssignmentsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $pa = $this->container->get('act_resource.assignment.previsional');

        return $this->render('ActResourceBundle:Assignment:previsional.html.twig', array(
            'pa' => $pa,
            'allTeams' => $em->getRepository('ActResourceBundle:Team')->findAll(),
            'allProjects' => $em->getRepository('ActResourceBundle:Project')->getProjects()
        ));
    }

    /**
     * Change the sub-task linked to an assignment in AJAX.
     * This action can be realized by a project CPT.
     *
     * @return Response in JSON
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function changeSubTaskAction()
    {
        $request = $this->getRequest();
        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        // Check that this is an AJAX request
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException($this->get('translator')->trans('only.available.ajax'));
        }

        // Get POST parameters
        $pid = $request->request->get('project_id');
        $day = $request->request->get('day');
        $rshort = $request->request->get('resource_short');

        // Create useful objects
        $project = $em->getRepository('ActResourceBundle:Project')->find($pid);
        if (!$project) {
            throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.project'));
        }

        $day = \DateTime::createFromFormat('d/m/Y', $day);
        $day->setTime(0,0,0);

        $resource = $em->getRepository('ActResourceBundle:Resource')->findOneBy(array('nameShort' => $rshort));
        if (!$resource) {
            throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.resource'));
        }

        $assignment = $em->getRepository('ActResourceBundle:Assignment')->findOneBy(array('resource' => $resource, 'project' => $project, 'day' => $day));
        if (!$assignment) {
            throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.assignment'));
        }

        // On vérifie les droits
        if(!$this->container->get('act.cptRights')->canChangeSubtask($assignment, $user) && !$this->get('security.context')->isGranted('ROLE_RP'))
            throw new AccessDeniedHttpException('Modification de sous-tâches uniquement autorisée aux CPT du projet et aux responsables de production');

        // On récupère la tâche concernée et on met à jour l'affectation
        $taskId = $request->request->get('subtask_id');
        if ($taskId == null) {
            $assignment->setSubTask(null);
        } else {
            $task = $em->getRepository('ActResourceBundle:Task')->find($taskId);
            if(!$task) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.task'));
            $assignment->setTask($task);
        }

        $em->persist($assignment);
        $em->flush();

        if($assignment->getSubtask() != null)
            $array['needs_subtask'] = false;
        else
            $array['needs_subtask'] = true;

        // Affichage warning si en dehors de la tâche / sous-tâche
        $array['outoftask'] = ($assignment->isOutOfTaskDates() ? 1 : 0);
        $array['outofsubtask'] = ($assignment->isOutOfSubtaskDates() ? 1 : 0);

        // Renvoi des données en JSON
        $array['result'] = 1;
        $response = new Response(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * AJAX function to load homepage dashboard data.
     * @return Response
     * @throws \Exception
     */
    public function getActiveProjectPerWeekAndResourceAction()
    {
        $result = array();
        $request = $this->container->get('request');
        $em = $this->container->get('doctrine')->getManager();

        if (!$request->isXmlHttpRequest()) {
            throw new \Exception('Only AJAX allowed');
        }

        // Gather data.
        $resource = $em->getRepository('ActResourceBundle:Resource')->find($request->get('resource'));
        $days = $request->get('days');

        // Compute the first and last day of week.
        $date = new \DateTime('now');
        $interval = new \DateInterval('P' . abs($days) . 'D');
        if ($days < 0) {
            $date->sub($interval);
        } else {
            $date->add($interval);
        }

        $dates = $this->get('act_main.date.manager')->findFirstAndLastDaysOfWeek($date);

        // Get plannings data.
        $planningData = $this->get('act_resource.week_planning_manager')->getWeekPlanning($resource, $dates['start'], $dates['end']);
        $weekProjects = $this->get('act_resource.weekly_projects_manager')->getWeekProjects($dates['start'], $dates['end']);

        // Render user week assignments.
        $result['user'] = $this->renderView('ActResourceBundle:Assignment:Include/user-week-assignments.html.twig', array(
            'planning' => $planningData
        ));

        // Render project week assignments.
        $result['project'] = $this->renderView('ActResourceBundle:Assignment:Include/project-week-assignments.html.twig', array(
            'projects' => $weekProjects
        ));

        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
