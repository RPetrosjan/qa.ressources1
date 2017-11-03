<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Act\ResourceBundle\Entity\Resource;
use Act\ResourceBundle\Entity\Project;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlleur de gestion des ressources
 *
 *
 */
class ResourceController extends Controller
{
    /**
     * Génère les informations nécéssaires pour construire le tableau récapitulatif
     * des affectation d'une ressource pour une semaine donnée
     */
    private function generateRecapInfos(Resource $resource,\DateTime $day)
    {
        $array = array();
        $em = $this->getDoctrine()->getManager();
        $array['projects'] = array();

        // Génération des dates de début et de fin de semaine
        $dates = $this->container->get('act_main.date.manager')->findFirstAndLastDaysOfWeek($day);
        $interval = \DateInterval::createFromDateString('1 day');
        $start = $dates['start'];
        $end = $dates['end'];
        $period = new \DatePeriod($start, $interval, $end);

        // On récupère les affectations de la semaine pour cette ressource et les jours fériés
        $assignments = $em->getRepository('ActResourceBundle:Assignment')->getAssignmentsForResource($resource->getId(), $start, $end);
        $bankholidays = $em->getRepository('ActResourceBundle:BankHoliday')->getBankHolidaysWithLocations($start, $end, $resource->getLocation());

        // On réorganise les données
        foreach ($assignments as $assignment) {
            if (!isset($array['projects'][$assignment->getProject()->getId()])) {
                $array['projects'][$assignment->getProject()->getId()] = array(
                    'name' => $assignment->getProject()->getName(),
                    'id' => $assignment->getProject()->getId(),
                    'days' => array()
                );
            }
        }

        $array['days'] = array();
        foreach ($period as $day) {
            $array['days'][$day->format('d/m')] = 0;
        }

        foreach ($array['projects'] as $pid => $project) {
            foreach ($period as $day) {
                foreach ($assignments as $assignment) {
                    if ($assignment->getProject()->getId() == $pid) {
                        if ($assignment->getDay()->format('d/m/Y') == $day->format('d/m/Y')) {
                            $array['days'][$day->format('d/m')] += (float) $assignment->getWorkload();
                            $array['projects'][$pid]['days'][$day->format('d/m')]['workload'] = (float) $assignment->getWorkload();
                            if($assignment->getCommontask())
                                $array['projects'][$pid]['days'][$day->format('d/m')]['task'] = $assignment->getCommontask()->getName();
                            else
                                $array['projects'][$pid]['days'][$day->format('d/m')]['task'] = null;
                            break;
                        }
                    }
                }

            }
        }

        $array['nbdaysperweek'] = $resource->getDaysPerWeek();

        // On regarde si la ressource est indisponible à certains jours de la semaine
        if ($start < $resource->getStart() || ($resource->getEnd() != null && $end > $resource->getEnd())) {
            foreach ($period as $day) {
                if ($day < $resource->getStart()) {
                    if (!isset($array['projects']['disabled'])) {
                        $array['projects']['disabled'] = array(
                            'name' => 'Indisponible',
                            'id' => null,
                            'days' => array()
                        );
                    }

                    $array['projects']['disabled']['days'][$day->format('d/m')]['workload'] = 1;
                    $array['projects']['disabled']['days'][$day->format('d/m')]['task'] = $this->get('translator')->trans('not.yet.in.company');
                    $array['days'][$day->format('d/m')] += 1;
                } elseif ($resource->getEnd() != null && $day > $resource->getEnd()) {
                    if (!isset($array['projects']['disabled'])) {
                        $array['projects']['disabled'] = array(
                            'name' => 'Indisponible',
                            'id' => null,
                            'days' => array()
                        );
                    }

                    $array['projects']['disabled']['days'][$day->format('d/m')]['workload'] = 1;
                    $array['projects']['disabled']['days'][$day->format('d/m')]['task'] = $this->get('translator')->trans('not.anymore.in.company');
                    $array['days'][$day->format('d/m')] += 1;
                }
            }
        }

        // On regarde si il y a un/des jours fériés
        if (count($bankholidays) > 0) {
            foreach ($period as $day) {
                foreach ($bankholidays as $bankholiday) {
                    if ($day == $bankholiday->getStart()) {
                        if (!isset($array['projects']['bankholiday'])) {
                            $array['projects']['bankholiday'] = array(
                                'name' => 'Jours fériés',
                                'id' => null,
                                'days' => array()
                            );
                        }

                        $array['projects']['bankholiday']['days'][$bankholiday->getStart()->format('d/m')]['workload'] = 1;
                        $array['projects']['bankholiday']['days'][$bankholiday->getStart()->format('d/m')]['task'] = $bankholiday->getName();
                        $array['days'][$bankholiday->getStart()->format('d/m')] += 1;
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Récupèration des affectations de la semaine pour une ressource - en AJAX
     * NB: utilisé dans le récapitulatif annuel lors du clic sur une case du tableau
     * @return Response (JSON)
     */
    public function getInfosSimpleAction()
    {
        $request = $this->getRequest();
        $em = $this->getDoctrine()->getManager();

        // On vérifie qu'on se trouve bien en AJAX
        if(!$request->isXmlHttpRequest())
            throw $this->createNotFoundException($this->get('translator')->trans('only.available.ajax'));

        // On récupère les paramètres
        $name_short = $request->request->get('resource_short');
        $day = $request->request->get('day');
        $day = \DateTime::createFromFormat('d/m/Y', $day);
        $day->setTime(0,0,0);

        // On récupère la ressource concernée
        $resource = $em->getRepository('ActResourceBundle:Resource')->findOneBy(array('nameShort' => $name_short));
        if (!$resource) {
            throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.resource'));
        }

        // On récupère les infos récapitulatives
        $array = $this->generateRecapInfos($resource, $day);

        // Renvoi des données en JSON
        $response = new Response(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Récupèration des affectations de la semaine pour une ressource - en AJAX
     * Avec la liste des tâches du projet et celle assignée a cette affectation
     * NB: utilisé dans le planning lors du clic sur une case du tableau
     * @return Response (JSON)
     */
    public function getInfosAction()
    {
        $request = $this->getRequest();
        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();
        $cptRights = $this->container->get('act.cptRights');

        // On vérifie qu'on se trouve bien en AJAX
        if(!$request->isXmlHttpRequest())
            throw $this->createNotFoundException($this->get('translator')->trans('only.available.ajax'));

        // On récupère les paramètres
        $project_id = $request->request->get('project_id');
        $name_short = $request->request->get('resource_short');
        $day = $request->request->get('day');
        $day = \DateTime::createFromFormat('d/m/Y', $day);
        $day->setTime(0,0,0);

        // On récupère la ressource concernée
        $resource = $em->getRepository('ActResourceBundle:Resource')->findOneBy(array('nameShort' => $name_short));
        if (!$resource) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.resource'));

        // On récupère le projet concerné
        $project = $em->getRepository('ActResourceBundle:Project')->getProjectWithTasks($project_id);
        if (!$project) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.project'));

        // On génére les infos récapitulative
        $array = $this->generateRecapInfos($resource, $day);

        // On cherche la tâche et la sous-tâche liées à l'affectation cliquée
        $assignment = $em->getRepository('ActResourceBundle:Assignment')->findOneBy(array('day' => $day, 'resource' => $resource, 'project' => $project));
        if ($assignment) {

            $array['comment'] = $assignment->getComment();

            if ($assignment->getSubtask()) {
                $array['selected']['subtask'] = $assignment->getSubtask()->getId();
                $array['selected']['task'] = $assignment->getCommontask()->getId();
            } else {
                $array['selected']['subtask'] = null;
                if($assignment->getCommontask())
                    $array['selected']['task'] = $assignment->getCommontask()->getId();
                else
                    $array['selected']['task'] = null;
            }
        }

        // 2.1 Génération du selecteur de tâche pour l'assignation
        // On génére le select des tâches, on ne prend que les tâches et les sous-tâches
        $array['tasks'] = array();
        foreach ($project->getMetatasks() as $mtask) {
            $array['tasks'][$mtask->getId()] = array();
            $array['tasks'][$mtask->getId()]['name'] = htmlspecialchars($mtask->getName());
            $array['tasks'][$mtask->getId()]['wksold'] = $mtask->getWorkloadSold();
            $array['tasks'][$mtask->getId()]['commontasks'] = array();

            foreach ($mtask->getCommontasks() as $task) {
                $go_on = false;
                if ($this->get('security.context')->isGranted('ROLE_RP')) {
                    $go_on = true;
                } elseif ($cptRights->isCPT($project, $user)) {
                    if ($assignment->getCommontask() != null && $assignment->getCommontask()->getId() == $task->getId()) {
                        $go_on = true;
                    } elseif ($assignment->getSubtask() != null) {
                        foreach ($task->getSubtasks() as $subtask) {
                            if ($subtask->getId() == $assignment->getSubtask()->getId()) {
                                $go_on = true; break;
                            }
                        }
                    }
                }

                if ($go_on) {
                    $array['tasks'][$mtask->getId()]['commontasks'][$task->getId()] = array();
                    $array['tasks'][$mtask->getId()]['commontasks'][$task->getId()]['name'] = htmlspecialchars($task->getName());
                    $array['tasks'][$mtask->getId()]['commontasks'][$task->getId()]['wksold'] = $task->getWorkloadSold();
                    $array['tasks'][$mtask->getId()]['commontasks'][$task->getId()]['subtasks'] = array();

                    foreach ($task->getSubTasks() as $subtask) {
                        if ($this->get('security.context')->isGranted('ROLE_RP') || $cptRights->hasAccess($subtask, $user)) {
                            $array['tasks'][$mtask->getId()]['commontasks'][$task->getId()]['subtasks'][$subtask->getId()]['name'] = htmlspecialchars($subtask->getName());
                            $array['tasks'][$mtask->getId()]['commontasks'][$task->getId()]['subtasks'][$subtask->getId()]['wksold'] = $subtask->getWorkloadSold();
                        }
                    }
                }
            }
        }

        // Renvoi des données en JSON
        $response = new Response(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Récupère les affectations pour une ressource donnée et un projet donné
     * Les options start, end, inverse peuvent être précisée dans la requête
     *
     * @return JSON
     */
    public function getAssignmentsAction(Resource $resource, Project $project)
    {
        $em = $this->container->get('doctrine')->getManager();
        $request = $this->container->get('request');
        $data = array();

        // Récupèration des paramètres
        $startDate = \DateTime::createFromFormat('d/m/Y', $request->query->get('start'));
        $endDate = \DateTime::createFromFormat('d/m/Y', $request->query->get('end'));
        $inverse = $request->query->get('inverse', false);

        // Chargement des affectations
        $assignments = $em->getRepository('ActResourceBundle:Assignment')->getResourceAssignmentsForProject($project, $resource, $startDate, $endDate, $inverse);

        // Formattage des données
        foreach ($assignments as $assignment) {
            $data[$assignment->getId()] = array(
                'day'      => array(
                    'dayLong' => $assignment->getDay()->format('d/m/Y'),
                    'dayShort' => $assignment->getDay()->format('d/m')
                ),
                'workload' => $assignment->getWorkload(),
                'project'  => array(
                    'id' => $assignment->getProject()->getId(),
                    'name' => $assignment->getProject()->getName(),
                    'nameShort' => $assignment->getProject()->getNameShort()
                )
            );
        }

        // Renvoi des données en JSON
        $response = new Response(json_encode($data));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Display details of a resource assignments.
     */
    public function detailsResourcePageAction(Resource $resource)
    {
        $data = array();
        $em = $this->container->get('doctrine')->getManager();

        // Get all assignments of the current resource.
        // @TODO improve this query by adding projects to avoid unecessary requests.
        $assignments = $em->getRepository('ActResourceBundle:Assignment')->findBy(array('resource' => $resource->getId()));
        if (empty($assignments)) {
            throw $this->createNotFoundException();
        }

        // Generate a data array.
        foreach($assignments as $assignment){
            $day = $assignment->getDay();
            $name = $assignment->getProject()->getName();

            if (!empty($data[$name])) {
                // If we have already found this project before, sum workload and compare dates.
                $data[$name]['workload'] += $assignment->getWorkload();
                if ($day < $data[$name]['days']['first']) {
                    $data[$name]['days']['first'] = $day;
                } elseif ($day > $data[$name]['days']['last']) {
                    $data[$name]['days']['last'] = $day;
                }
            } else {
                // If not, we just initialize the value.
                $data[$name]['workload'] = $assignment->getWorkload();
                $data[$name]['days']['first'] = $day;
                $data[$name]['days']['last'] = $day;
                $data[$name]['project'] = $assignment->getProject();
            }
        }

        // Display template.
        return $this->render('ActResourceBundle:Resource:details.html.twig', array(
            'resource' => $resource,
            'assignments' => $assignments,
            'data' => $data
        ));
    }
}
