<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Act\ResourceBundle\Entity\Project;
use Act\ResourceBundle\Entity\Team;
use Act\ResourceBundle\Entity\Assignment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controlleur de gestion du planning projet
 *
 */
class PlanningController extends Controller
{
    /**
     * Affichage du planning de modification d'un projet
     * avec le tableau Excel handsontable
     *
     * @param Project $project
     */
    public function showAction(Project $project)
    {
        $em = $this->container->get('doctrine')->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        // On vérifie les droits - Uniquement accessible aux RP
        if (!$this->container->get('security.context')->isGranted('ROLE_RP')) {
            throw new AccessDeniedHttpException($this->container->get('translator')->trans('access.limited.to.rp'));
        }

        // Création du planningManager
        $planningManager = $this->container->get('act_resource.planning_manager');
        $planningManager->setProject($project);

        // On regarde si c'est un projet préféré de l'utilisateur
        $pproject = $em->getRepository('ActResourceBundle:PreferedProject')->findBy(array('user' => $user, 'project' => $project));

        // Traitement du "referer"
        $referer = $this->container->get('request')->query->get('referer');
        if($referer == null)
            $referer = $this->generateUrl('act_resource_project_show', array('id' => $project->getId()));

        return $this->render('ActResourceBundle:RPProject:show.html.twig', array(
            'manager'    => $planningManager,
            'project'    => $project,
            'isPrefered' => ($pproject != null ? true : false),
            'referer'    => $referer
        ));
    }

    /**
     * Récupèration du planning complet d'une équipe
     * Attention! Uniquement disponible en AJAX
     *
     * @param Project $project
     * @param Team    $team
     *
     * @return JSON
     */
    public function getPlanningAction(Project $project, Team $team)
    {
        // On vérifie les droits - Uniquement accessible aux RP
        if (!$this->container->get('security.context')->isGranted('ROLE_RP')) {
            throw new AccessDeniedHttpException($this->container->get('translator')->trans('access.limited.to.rp'));
        }

        // Création du planingManager
        $planningManager = $this->container->get('act_resource.planning_manager');
        $planningManager->setProject($project, $team);

        // On récupère le planning de l'équipe concernée
        $planning = $planningManager->getTeamPlanning($team);

        // Génération des données JSON et renvoi
        $data = $planning->getAllData();

        $response = new Response($data);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Sauvegarde les affectations d'un planning
     * provenant d'une table handsontable
     *
     * @param Project $project
     *
     * @return JSON
     */
    public function saveAction(Project $project)
    {
        $em = $this->container->get('doctrine')->getManager();
        $request = $this->container->get('request');
        $response = null;

        // On vérifie les droits - Uniquement accessible aux RP
        if (!$this->container->get('security.context')->isGranted('ROLE_RP')) {
            throw new AccessDeniedHttpException($this->container->get('translator')->trans('access.limited.to.rp'));
        }

        // Récupération et décodage des données
        $datas = json_decode($request->request->get('data'));
        $start = \DateTime::createFromFormat('d/m/Y', $request->request->get('start'))->setTime(0,0,0);
        $end = \DateTime::createFromFormat('d/m/Y', $request->request->get('end'))->setTime(0,0,0);

        // Récupération de toutes les affectations pour le projet, et la période affichée
        // Et tri dans un tableau associatif pour accès plus rapide et réduction du nbr de queries
        $assignmentsDB = $em->getRepository('ActResourceBundle:Assignment')->getAssignmentsForProject($project, $start, $end);
        $assignments = array();
        foreach ($assignmentsDB as $a) {
            if (!isset($assignments[$a->getResource()->getId()])) {
                $assignments[$a->getResource()->getId()] = array();
            }
            $assignments[$a->getResource()->getId()][$a->getDay()->format('d/m/Y')] = $a;
        }

        $added = 0;
        $modified = 0;
        $removed = 0;

        try {
            // If very large data
            set_time_limit(0);
            $em->beginTransaction();

            foreach ($datas as $resourceId => $resourceData) {
                // Récupération de la ressource
                $resource = $em->getRepository('ActResourceBundle:Resource')->findOneBy(array('id' => $resourceId));

                foreach ($resourceData->days as $data) {
                    // Création de la datetime du jour
                    $day = \DateTime::createFromFormat('d/m/Y', $data->day)->setTime(0,0,0);

                    // Récupération des tâches
                    // @TODO : attention si changement de projet, on ne peut que avoir des tâches du projet
                    $commontask = null; $subtask = null;
                    if(isset($data->task)) $commontask = $em->getRepository('ActResourceBundle:Task')->find($data->task->id);
                    if(isset($data->subtask)) $subtask = $em->getRepository('ActResourceBundle:Task')->find($data->subtask->id);

                    // Try to get an existing assignment
                    $previousAssignment = null; $new = true;
                    if (isset($assignments[$resourceId][$data->day])) {
                        $previousAssignment = $assignments[$resourceId][$data->day];
                        $new = false;
                    }

                    // Traitements selon les cas
                    if (isset($data->workload)) {

                        $assignment = new Assignment();
                        $assignment->setWorkload($data->workload);
                        $assignment->setProject($project);
                        $assignment->setResource($resource);
                        $assignment->setDay($day);
                        $assignment->setCommontask($commontask);
                        $assignment->setSubtask($subtask);
                        $assignment->setComment($data->comment);

                        if (!$new) {
                            // Compare both assignments to check if they need update
                            if (!$previousAssignment->hasSameData($assignment)) {
                                // We need to update, there were changes
                                $previousAssignment->copyData($assignment);
                                $modified++;
                                $em->flush($previousAssignment);
                            } else {
                                // No need to update, no changes
                            }
                        } else {
                            $em->persist($assignment);
                            $em->flush($assignment);
                            $added++;
                        }

                    } else {
                        if ($previousAssignment != null) {
                            $removed++;
                            $em->remove($previousAssignment);
                            $em->flush($previousAssignment);
                        }
                    }
                }
            }

            // Sauvegarde des modifications
            $em->commit();

            $message = $modified.' affectaction(s) modifiée(s), '.$added.' affectation(s) créée(s), '.$removed.' affectation(s) supprimée(s)';
            $response = new Response(json_encode(array('success' => $message)));
            $response->headers->set('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $em->rollback();
            $response = new Response(json_encode(array('error' => $e->getMessage())));
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }

    /**
     * Affichage du planning de modification d'un projet
     * avec le tableau Excel handsontable - pour une équipe donnée
     *
     * @param Team $team
     */
    public function showTeamAction(Team $team)
    {
        // On vérifie les droits - Uniquement accessible aux RP
        if (!$this->container->get('security.context')->isGranted('ROLE_RP')) {
            throw new AccessDeniedHttpException($this->container->get('translator')->trans('access.limited.to.rp'));
        }

        // Création du planningManager
        $planningManager = $this->container->get('act_resource.planning_manager');
        $planningManager->setTeam($team);

        // Traitement du "referer"
        $referer = $this->container->get('request')->query->get('referer');
        if($referer == null)
            $referer = $this->generateUrl('act_resource_team_projects', array('id' => $team->getId()));

        return $this->render('ActResourceBundle:Team:projectsPlanning.html.twig', array(
            'manager'    => $planningManager,
            'team'       => $team,
            'referer'    => $referer
        ));
    }
}
