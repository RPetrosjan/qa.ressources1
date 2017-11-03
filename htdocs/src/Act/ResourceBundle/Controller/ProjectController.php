<?php

namespace Act\ResourceBundle\Controller;

use Act\ResourceBundle\Entity\MetaTask;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Act\ResourceBundle\Entity\Project;
use Act\ResourceBundle\Entity\Team;
use Act\ResourceBundle\Entity\Comment;
use Act\ResourceBundle\Entity\Assignment;
use Act\ResourceBundle\Entity\Link;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Controleur de gestion des projets
 *
 * C'est ici que se passe :
 *  - l'affichage de la liste des projets
 *  - la gestion des tâches du projet
 *  - la gestion des affectations du projet
 *  - la gestion des commentaires du projet
 *  - la gestion des liens du projet
 *  - l'affichage du planning de projet
 *
 * Vu que la majorité des objets tournent autour des projets, ce controleur
 * est central, et son nombre de ligne est donc justifié.
 *
 * NB: Pas besoin de vérifier le ROLE_USER car le firewall principal bloque tout
 * accès en tant qu'anonyme sur toutes les pages du site.
 */
class ProjectController extends Controller
{
    /**
     * Cherche les projets qui correspondent à ce nom (autocomplete)
     * @param  string   $name un nom de projet
     * @return Response (JSON)
     */
    public function findByNameAction($name = null)
    {
        $em = $this->getDoctrine()->getManager();
        $projects = $em->getRepository('ActResourceBundle:Project')->findProjectsByName($name);
        $array = array();
        foreach ($projects as $project) {
            $array[] = array(
              'id' => $project->getId(),
              'name' => $project->getName(),
              'link' => $this->generateUrl('act_resource_project_show', array('id' => $project->getId()))
            );
        }

        if (count($array) == 0) {
            $array[] = array(
              'name' => $this->get('translator')->trans('no.results'),
              'link' => '#'
            );
        }

        $response = new Response(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Affichage du planning d'un projet
     * Permet de gérer les affectations ainsi que les tâches
     *
     * @param  Project  $project
     * @return Response
     */
    public function showAction(Project $project)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        // Création du planingManager
        $planningManager = $this->container->get('act_resource.planning_manager');
        $planningManager->setProject($project);

        // On charge toutes les ressources, utilisé par la fonctionnalité : remplacer ressource X par ressource Y
        $resources = $em->getRepository('ActResourceBundle:Resource')->getResourcesWithTeam();

        // On regarde si c'est un projet préféré de l'utilisateur
        $pproject = $em->getRepository('ActResourceBundle:PreferedProject')->findBy(array('user' => $user, 'project' => $project));

        return $this->render('ActResourceBundle:Project:show.html.twig', array(
            'manager'    => $planningManager,
            'project'    => $project,
            'resources' => $resources,
            'isPrefered' => ($pproject != null ? true : false)
        ));
    }

    /**
     * Récupèration du planning complet d'une équipe
     * Attention! Uniquement disponible en AJAX
     *
     * @param  Project  $project
     * @param  Team     $team
     * @return Response
     */
    public function getplanningAction(Project $project, Team $team)
    {
        // Création du planingManager
        $planningManager = $this->container->get('act_resource.planning_manager');
        $planningManager->setProject($project, $team);

        // On récupère le planning de l'équipe concernée
        $planning = $planningManager->getTeamPlanning($team);

        // Rendu de la vue et renvoi du HTML
        return $this->render('ActResourceBundle:Project:onlyplanning.html.twig', array(
            'planning'  => $planning,
            'manager'   => $planningManager
        ));
    }

    /**
     * Affichage des tâches de ce projet
     * @param  int      $id l'id du projet
     * @return Response
     */
    public function tasksAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $request = $this->getRequest();
        $user = $this->container->get('security.context')->getToken()->getUser();
        $cptRights = $this->container->get('act.cptRights');

        $project = $em->getRepository('ActResourceBundle:Project')->getProjectWithTasksFull($id);
        if(!$project) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.project'));

        $teams = $em->getRepository('ActResourceBundle:Team')->getTeamsWithResources();

        $userTeamId = null;
        if ($user->getResource()) {
            $userTeamId = $user->getResource()->getTeam()->getId();
        }

        $referer = $request->query->get('referer');
        if($referer == null)
            $referer = $this->generateUrl('act_resource_project_show', array('id' => $id));

        return $this->render('ActResourceBundle:Project:tasks.html.twig', array(
            'project'    => $project,
            'teams'      => $teams,
            'userTeamId' => $userTeamId,
            'referer'    => $referer,
            'cptRights' => $cptRights
        ));
    }

    /**
     * Ajout d'une affectation au projet (en AJAX)
     * @param  int  $id l'id du projet
     * @return JSON
     */
    public function addassignmentAction($id)
    {
        $request = $this->getRequest();
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $array = array();

        // On vérifie les droits
        if(!$this->get('security.context')->isGranted('ROLE_RP'))
            throw new AccessDeniedHttpException($this->get('translator')->trans('access.limited.to.rp'));

        // On vérifie qu'on est bien en AJAX
        if(!$request->isXmlHttpRequest())
            throw $this->createNotFoundException($this->get('translator')->trans('only.available.ajax'));

        // On récupère le projet concerné
        $project = $em->getRepository('ActResourceBundle:Project')->find($id);
        if (!$project) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.project'));

        // On change une possible virgule en point
        $workload = $request->request->get('workload_affected');
        if (strpos($workload, ',') != 0) {
            $workload = str_replace(',', '.', $workload);
        }

        // On vérifie que la valeur du workload affecté est numérique ou nulle
        if (!is_numeric($workload) && $workload != '') {
            // Workload incorrect, on renvoie une erreur
            $array['result'] = 0;
            $array['reason'] = $this->get('translator')->trans('invalid.workload');
            $response = new Response(json_encode($array));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        // On récupère le jour concerné
        $day = \DateTime::createFromFormat('d/m/Y', $request->request->get('day'));
        $day->setTime(0,0,0);
        $array['idDayFormat'] = $day->format('d-m-Y');

        // On récupère la ressource concernée
        $resource = $em->getRepository('ActResourceBundle:Resource')->findOneBy(array('nameShort' => $request->request->get('resource_short')));
        if(!$resource) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.resource'));
        $array['teamId'] = $resource->getTeam()->getId();

        // On récupère la tâche concernée si elle existe
        $task = null;
        if ($request->request->get('task') != null && $request->request->get('task') != '') {
            $task = $em->getRepository('ActResourceBundle:Task')->find($request->request->get('task'));
            if(!$task) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.task'));
            $array['hasTask'] = 1;
        } else {
            $array['hasTask'] = 0;
        }

        // On recherche une affectation préexistente
        $assignment = $em->getRepository('ActResourceBundle:Assignment')->findOneBy(array('resource' => $resource, 'project' => $project, 'day' => $day));
        if (!$assignment) {
            // Sinon on créé une nouvelle affectation
            $assignment = new Assignment();
            $assignment->setDay($day);
            $assignment->setProject($project);
            $assignment->setResource($em->getRepository('ActResourceBundle:Resource')->findOneBy(array('nameShort' => $request->request->get('resource_short'))));

            $before_unsold = array();
            if ($task != null) {
                // On calcule le temps invendu pour la tâche future à laquelle l'affectation va être liée
                $before_unsold = $this->container->get('act.project')->computeUnsoldWithTask($task);
            }
        } else {
            // On calcule le temps invendu, pour les autres affectations ayant la même tâche que celle que l'on modifie
            // + on calcule le temps invendu pour la tâche future à laquelle l'affectation va être liée
            $before_unsold = $this->container->get('act.project')->computeUnsold($assignment);
            if ($task != null) {
                $before_unsold = array_merge($before_unsold, $this->container->get('act.project')->computeUnsoldWithTask($task));
            }
        }

        if($assignment->getCommontask() == null && $task != null)
            $array['addedTask'] = 1;
        elseif($assignment->getCommontask() != null && $task == null)
            $array['removedTask'] = 1;

        // Saving previous task for unsold computation
        $previousTask = null;
        if($assignment->getSubtask() != null)
            $previousTask = $assignment->getSubtask();
        elseif($assignment->getCommontask() != null)
            $previousTask = $assignment->getCommontask();

        // Si le workload est nul, c'est une suppression d'une affectation existante
        if ($workload == '' || $workload == 0) {
            // Suppression de l'affectation
            $array['value'] = null;
            $array['unsold'] = null;
            $array['lastValue'] = $assignment->getWorkload();
            $array['lastUnsold'] = $assignment->getUnsold();

            $em->remove($assignment);
            $project->removeAssignment($assignment);
            $em->flush();

            $after_unsold = array();
            if ($previousTask != null) {
                // On calcule le temps invendu pour les affectations liées à la tâche de cette ancienne affectation
                $after_unsold = $this->container->get('act.project')->computeUnsoldWithTask($previousTask);
            }
        } else {
            // Sinon on met simplement à jour la tâche et le workload affecté
            $array['lastValue'] = $assignment->getWorkload();
            $array['lastUnsold'] = $assignment->getUnsold();

            $assignment->setWorkload($workload);
            $assignment->setTask($task);
            $assignment->setUpdated(new \DateTime());

            $comment = $request->request->get('comment');
            $assignment->setComment($comment);

            $em->persist($assignment);
            $em->flush();

            $array['value'] = $assignment->getWorkload();
            $array['unsold'] = $assignment->getUnsold();
            $array['daysPerWeek'] = $resource->getDaysPerWeek();

            // Hightlight des erreurs
            // On cherche le total des affectations du jour ainsi que le total de la semaine
            $dates = $this->get('act_main.date.manager')->findFirstAndLastDaysOfWeek($assignment->getDay());
            $weekAssignments = $em->getRepository('ActResourceBundle:Assignment')->getAssignmentsForResource($assignment->getResource(), $dates['start'], $dates['end']);

            // Total du jour
            $array['dayWorkload'] = $assignment->getWorkload();
            // Total de la semaine
            $array['weekWorkload'] = 0;

            // On parcours les affectations de la semaine
            foreach ($weekAssignments as $a) {
                // On calcule le total du jour
                if($a->getDay() == $assignment->getDay() && $a->getProject() != $project)
                    $array['dayWorkload'] += $a->getWorkload();

                $array['weekWorkload'] += $a->getWorkload();
            }

            // Affichage warning si en dehors de la tâche / sous-tâche
            $array['outoftask'] = ($assignment->isOutOfTaskDates() ? 1 : 0);
            $array['outofsubtask'] = ($assignment->isOutOfSubtaskDates() ? 1 : 0);

            // On calcule le temps invendu pour les affectations liées à la tâche
            // de l'affectation + le temps invendu des affectations liées à l'ancienne tâche de l'affectation
            $after_unsold = $this->container->get('act.project')->computeUnsold($assignment);
            if ($previousTask != null) {
                $after_unsold = array_merge($after_unsold, $this->container->get('act.project')->computeUnsoldWithTask($previousTask));
            }
        }

        $array['updates'] = $this->container->get('act.project')->mergeUnsold($before_unsold, $after_unsold);
        // Renvoi des données en JSON
        $array['result'] = 1;
        $array['projectId'] = $project->getId();

        $response = new Response(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Remplace sur ce projet une ressource X par une ressource Y
     * Toutes les affectations de X pour ce projet seront désormais assignées à Y
     *
     * Si la ressource remplaçante a déjà une affectation un jour donné sur ce même projet,
     * on ajoute le temps à l'affectation déjà existante et on supprime l'ancienne.
     * Dans ce cas, une notification est affichée pour les jours où il y a eu conflit.
     *
     * @param  int      $id l'id du projet
     * @return Response
     */
    public function replaceAction($id)
    {
        if(!$this->get('security.context')->isGranted('ROLE_RP') )
            throw new AccessDeniedHttpException($this->get('translator')->trans('access.limited.to.rp'));

        $em = $this->getDoctrine()->getManager();
        $request = $this->get('request');

        // On récupère le projet
        $project = $em->getRepository('ActResourceBundle:Project')->find($id);
        if (!$project) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.project'));

        // On récupère le remplacé et le remplaçant
        $replaced = $em->getRepository('ActResourceBundle:Resource')->find($request->request->get('from'));
        $replacing = $em->getRepository('ActResourceBundle:Resource')->find($request->request->get('to'));

        if (!$replaced || !$replacing) {
            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('unable.to.find.selected.resources'));

            return $this->redirect($this->generateUrl('act_resource_project_show', array('id' => $project->getId())));
        } else {
            $nbA = 0; $assignConflicts = array();

            // On recherche toutes les affectations de la ressource à remplacer
            foreach ($em->getRepository('ActResourceBundle:Assignment')->getAssignmentsForThisResourceAndProject($replaced, $project) as $assignment) {
                // Il faut vérifier si il existe déjà une affectation ce jour là, et sur ce projet pour le remplaçant
                // Si c'est le cas, on fusionne les deux affectations et on en averti le manager
                $as = $replacing->getAssignment($project, $assignment->getDay());
                if ($as != null) {
                    // Alerte ! déjà une affectation ce jour ci ! On fusionne les deux...
                    $as->addWorkload($assignment->getWorkload());
                    // On supprime l'ancienne affectation
                    $em->remove($assignment);
                    $em->persist($as);
                    // On mémorise qu'il y a eu conflit pour le signaler
                    $assignConflicts[] = $as;
                } else {
                    // Pas de problèmes, on change juste la ressource
                    $assignment->setResource($replacing);
                    $em->persist($assignment);
                }
                $nbA++;
            }

            // Sauvegarde des modifications et notification
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('transfert.assignments', array('%number%' => $nbA, '%from%' => $replaced, '%to%' => $replacing)));

            // Notification des fusions
            if (count($assignConflicts) > 0) {
                $txt = '';
                foreach ($assignConflicts as $assignment) {
                    $txt .= $assignment->getDay()->format('d/m/Y').', ';
                }
                $this->get('session')->getFlashBag()->add('warning', $this->get('translator')->trans('conflict.at.dates', array('%txt%' => $txt)));

            }
        }

        return $this->redirect($this->generateUrl('act_resource_project_show', array('id' => $project->getId())));
    }

    /**
     * Réalise le décalage du projet dans le temps, d'un certain nombre de
     * jours ouvrés, soit dans le passé soit dans le futur.
     *
     * Décallage donc des affectations mais aussi des tâches.
     *
     * @param  int      $id l'id du projet concerné
     * @return Response
     */
    public function shiftAction($id)
    {
        if(!$this->get('security.context')->isGranted('ROLE_RP') )
            throw new AccessDeniedHttpException($this->get('translator')->trans('access.limited.to.rp'));

        $em = $this->getDoctrine()->getManager();
        $request = $this->get('request');

        $project = $em->getRepository('ActResourceBundle:Project')->find($id);
        if (!$project) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.project'));

        // On récupère le nombre de jours ouvrés
        $nbdays = $request->request->get('days');
        if (!is_numeric($nbdays) || intval($nbdays, 10) <= 0) {
            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('nbdays.must.be.positive'));

            return $this->redirect($this->generateUrl('act_resource_project_show', array('id' => $project->getId())));
        } else {
            $nbdays = intval($nbdays, 10);
        }

        // On récupère la "direction" dans le temps : 0 => passé, 1 => futur
        $timeDirection = $request->request->get('timeDirection');
        $timeDirectionLiteral = ($timeDirection == 1 ? $this->get('translator')->trans('future') : $this->get('translator')->trans('past'));

        // On check quel type de décalage on veut réaliser
        $shiftType = $request->request->get('project-shift');
        $nbT = 0;
        $nbA = 0;

        if ($shiftType == 0 or $shiftType == 2) {
            // On doit maintenant prendre toutes les affectations et toutes les tâches du projet, et les décaller de $nbdays jours
            // dans le passé ou futur selon la $timeDirection
            $tasks = $project->getTasks();
            foreach ($tasks as $task) {
                $task->shift($nbdays, $timeDirection);
                $em->persist($task);
                $nbT++;
            }
        }

        if ($shiftType == 1 or $shiftType == 2) {
            // On cherche les affectations de ce projet classé selon la date du jour, et dans un ordre différent selon la timeDirection
            // pour respecter la clé unique d'une affectation par projet/jour/ressource lors du décalage.
            $assignments = $em->getRepository('ActResourceBundle:Assignment')->getAssignmentsForThisProjectOrderByDay($project, $timeDirection);
            foreach ($assignments as $assignment) {
                $assignment->shift($nbdays, $timeDirection);
                $em->persist($assignment);
                $nbA++;
            }
        }

        // On sauvegarde le tout
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('project.shift.message', array('%nbdays%' => $nbdays, '%timedirection%' => $timeDirectionLiteral, '%nbtask%' => $nbT, '%nbass%' => $nbA)));

        return $this->redirect($this->generateUrl('act_resource_project_show', array('id' => $project->getId())));
    }

    /**
     * Ajout d'une tâche au projet
     * @param  int      $id l'id du projet
     * @return Response
     */
    public function addtaskAction(Project $project)
    {
        $em = $this->getDoctrine()->getManager();
        $request = $this->get('request');
        $user = $this->container->get('security.context')->getToken()->getUser();

        $referer = $request->request->get('referer');
        if($referer == null)
            $referer = $this->generateUrl('act_resource_project_show', array('id' => $project->getId()));

        // Récupèration des paramètres
        $type = $request->request->get('type');
        $name = $request->request->get('name');

        if (preg_match("#[0-9]{2}/[0-9]{2}/[0-9]{4}#", $request->request->get('start'))) {
            $start = \DateTime::createFromFormat('d/m/Y', $request->request->get('start'));
        } else {
            $start = \DateTime::createFromFormat('d/m/y', $request->request->get('start'));
        }

        if (preg_match("#[0-9]{2}/[0-9]{2}/[0-9]{4}#", $request->request->get('end'))) {
            $end = \DateTime::createFromFormat('d/m/Y', $request->request->get('end'));
        } else {
            $end = \DateTime::createFromFormat('d/m/y', $request->request->get('end'));
        }

        $workload = $request->request->get('workloadsold');
        $teams = $request->request->get('teams');
        $parent = $request->request->get('parent');

        // Vérifications données
        $redirect = false;
        if ($name == '') {
            $this->get('session')->getFlashBag()->add('error', 'Veuillez saisir un nom de tâche'); $redirect = true;
        }

        if (!$start) {
            $this->get('session')->getFlashBag()->add('error', 'Veuillez saisir une date de début'); $redirect = true;
        }

        if (!$end) {
            $this->get('session')->getFlashBag()->add('error', 'Veuillez saisir une date de fin'); $redirect = true;
        }

        if ($redirect) {
            return $this->redirect($this->generateUrl('act_resource_project_tasks', array('id' => $project->getId(), 'referer' => $referer)));
        }

        // Transformation "," en "."
        if (strpos($workload, ',') != 0) {
            $workload = str_replace(',', '.', $workload);
        }

        // Récupèration de la tâche parente
        $parentTask = $em->getRepository('ActResourceBundle:Task')->find($parent);
        if (!$parentTask) {
            // Si métatâche : seulement RP
            if(!$this->get('security.context')->isGranted('ROLE_RP'))
                throw new AccessDeniedHttpException($this->get('translator')->trans('access.limited.to.rp'));

            $task = new MetaTask();
        }

        // On regarde le type de la tâche pour savoir quoi créer comme enfant
        if ($parentTask instanceof MetaTask) {
            // Si tâche : seulement RP
            if(!$this->get('security.context')->isGranted('ROLE_RP'))
                throw new AccessDeniedHttpException($this->get('translator')->trans('access.limited.to.rp'));

            $task = new \Act\ResourceBundle\Entity\CommonTask();
            $task->setMetatask($parentTask);
        } elseif ($parentTask instanceof \Act\ResourceBundle\Entity\CommonTask) {
            if(!$this->container->get('act.cptRights')->canCreateChilds($parentTask, $user) && !$this->get('security.context')->isGranted('ROLE_RP'))
                throw new AccessDeniedHttpException($this->get('translator')->trans('access.limited.to.rp'));

            $task = new \Act\ResourceBundle\Entity\SubTask();
            $task->setCommontask($parentTask);
        } elseif ($task == null) {
            throw $this->createNotFoundException($this->get('translator')->trans('invalid.parent.task'));
        }

        $task->setName($name);

        // Vérification dates
        if ($start <= $end) {
            $task->setStart($start);
            $task->setEnd($end);
        } else {
            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('invalid.date.interval'));

            return $this->redirect($this->generateUrl('act_resource_project_tasks', array('id' => $project->getId(), 'referer' => $referer)));
        }

        $task->setProject($project);

        // Vérifications workload
        if ($workload != '' && !is_numeric($workload) || $workload < 0) {
            $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('invalid.workload.sold'));

            return $this->redirect($this->generateUrl('act_resource_project_tasks', array('id' => $project->getId(), 'referer' => $referer)));
        } elseif ($workload != '') {
            $task->setWorkloadSold($workload);
        } else {
            $task->setWorkloadSold(null);
        }

        // Traitements des équipes et profils
        if ($teams != null) {
            foreach ($teams as $t) {
                if (strpos($t, 'team-') !== false) {
                    $team = $em->getRepository('ActResourceBundle:Team')->find(substr($t, 5));
                    if (!$team) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.team'));

                    $task->addTeam($team);
                } else {
                    $teamprofile = $em->getRepository('ActResourceBundle:TeamProfile')->find(substr($t, 8));
                    if (!$teamprofile) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.teamprofile'));

                    $task->addTeamProfile($teamprofile);
                }
            }
        }

        $em->persist($task);
        $em->persist($project);
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('created.task'));

        return $this->redirect($this->generateUrl('act_resource_project_tasks', array('id' => $project->getId(), 'referer' => $referer)));
    }

    /**
     * Gestion des équipes préférées pour le projet
     * @param  Project $project
     * @return Response
     */
    public function preferedTeamsAction(Project $project)
    {
        $em = $this->container->get('doctrine')->getManager();
        $request = $this->container->get('request');
        $user = $this->container->get('security.context')->getToken()->getUser();

        if ($request->getMethod() == 'POST') {
            // on supprime les anciennes équipes choisies
            $previousPreferedTeams = $em->getRepository('ActResourceBundle:PreferedTeam')->findBy(array('user' => $user,'project' => $project));
            foreach ($previousPreferedTeams as $prevPT) {
                $em->remove($prevPT);
            }

            $em->flush();

            // On enregistre les nouvelles
            $teams = $request->request->get('teams');
            if (is_array($teams)) {
                foreach ($teams as $id) {
                    $prefTeam = new \Act\ResourceBundle\Entity\PreferedTeam();
                    $prefTeam->setProject($project);
                    $prefTeam->setUser($user);

                    $team = $em->getRepository('ActResourceBundle:Team')->find($id);
                    if(!$team) continue;

                    $prefTeam->setTeam($team);
                    $em->persist($prefTeam);
                }

                $em->flush();
                $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('saved.modifications'));
            }

            $referer = $request->request->get('referer');
            if(!$referer)
                $referer = $this->generateUrl('act_resource_project_show', array('id' => $project->getId()));

            return $this->redirect($referer);
        } else {
            // On affiche les équipes préférées
            $preferedTeams = $em->getRepository('ActResourceBundle:PreferedTeam')->findBy(array('user' => $user,'project' => $project));
            $teams = $em->getRepository('ActResourceBundle:Team')->findAll();

            return $this->render('ActResourceBundle:Project:preferedteams.html.twig', array(
                'preferedTeams' => $preferedTeams,
                'teams'         => $teams,
                'project'       => $project,
                'referer'       => $request->query->get('referer')
            ));
        }
    }

    /**
     * Sauvegarde le réordonnancement des tâches du projet
     * @param  Project $project
     * @return Response
     */
    public function saveTaskSortableAction(Project $project)
    {
        $em = $this->getDoctrine()->getManager();
        $request = $this->get('request');
        $user = $this->container->get('security.context')->getToken()->getUser();
        $cptRights = $this->container->get('act.cptRights');

        // Vérification des droits
        if(!$this->get('security.context')->isGranted('ROLE_RP') && !$cptRights->isCPT($project, $user))
            throw new AccessDeniedHttpException($this->get('translator')->trans('task.move.access.message'));

        $tmp = $request->request->get('tasks');
        $tasks = json_decode($tmp);

        // NB : trop compliqué de traiter tous les cas possibles, on prend la hiérarchie et on recréé les tâches et sous-tâches...
        foreach ($tasks as $mData) {
            $metatask = $em->getRepository('ActResourceBundle:MetaTask')->find($mData->id);
            if (!$metatask) {
                continue;
                throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.metatask'));
            }

            if (isset($mData->children)) {
                foreach ($mData->children as $cData) {
                    $commontask = $em->getRepository('ActResourceBundle:Task')->find($cData->id);
                    if (!$commontask) {
                        continue;
                        throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.task'));
                    }

                    // On vérifie juste si c'était une sous-tâche, avant, pour bloquer le CPT si il tente
                    // de transformer une sous-tâche en tâche, malgré la limitation côté client
                    if ($commontask instanceof \Act\ResourceBundle\Entity\SubTask && !$this->get('security.context')->isGranted('ROLE_RP')) {
                        throw new AccessDeniedHttpException($this->get('translator')->trans('subtask.transform.task'));
                    }

                    $newCommonTask = new \Act\ResourceBundle\Entity\CommonTask();
                    // On défini la métatâche
                    $newCommonTask->setMetatask($metatask);
                    $newCommonTask->getMetatask()->addCommontask($newCommonTask);
                    // On copie les données
                    $newCommonTask->hydrateWithOtherTask($commontask);
                    // On transfère les affectations
                    foreach ($commontask->getAssignments() as $ass) {
                        // On retire l'affectation à l'ancienne tâche
                        if($ass->getCommontask())
                            $ass->getCommontask()->removeAssignment($ass);
                        // On retire l'affectation à l'ancienne sous-tâche
                        if($ass->getSubtask())
                            $ass->getSubtask()->removeAssignment($ass);
                        // On ajoute l'affectation à la nouvelle tâche
                        $newCommonTask->addAssignment($ass);
                        // On met à jour la tâche de l'affectation
                        $ass->setCommontask($newCommonTask);
                        // On met à jour la sous-tâche de l'affectation
                        $ass->setSubtask(null);
                    }
                    // On met à jour les dates
                    $newCommonTask->ensureLinkedTasksDates();
                    // On supprime l'ancienne tâche
                    $em->remove($commontask);

                    if (isset($cData->children)) {
                        foreach ($cData->children as $sData) {
                            $subtask = $em->getRepository('ActResourceBundle:Task')->find($sData->id);
                            if (!$subtask) {
                                continue;
                                throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.subtask'));
                            }

                            $newSubTask = new \Act\ResourceBundle\Entity\SubTask();
                            // On défini la tâche parente
                            $newSubTask->setCommontask($newCommonTask);
                            $newSubTask->getCommontask()->addSubtask($newSubTask);
                            // On copie les données
                            $newSubTask->hydrateWithOtherTask($subtask);
                            // On transfère les affectations
                            foreach ($subtask->getAssignments() as $ass) {
                                // On retire l'affectation à l'ancienne tâche
                                if($ass->getCommontask())
                                    $ass->getCommontask()->removeAssignment($ass);
                                // On retire l'affectation à l'ancienne sous-tâche
                                if($ass->getSubtask())
                                    $ass->getSubtask()->removeAssignment($ass);
                                // On ajoute l'affectation à la nouvelle sous-tâche
                                $newSubTask->addAssignment($ass);
                                // On ajoute l'affectation à la tâche parente
                                $newSubTask->getCommontask()->addAssignment($ass);
                                // On met à jour la tâche de l'affectation
                                $ass->setCommontask($newSubTask->getCommontask());
                                // On met à jour la sous-tâche de l'affectation
                                $ass->setSubtask($newSubTask);
                            }
                            // On met à jour les dates
                            $newSubTask->ensureLinkedTasksDates();
                            // On supprime l'ancienne sous-tâche
                            $em->remove($subtask);
                        }
                    }
                }
            }
            $em->persist($metatask);
        }
        $em->flush();

        $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('saved.modifications'));

        return $this->redirect($this->generateUrl('act_resource_project_tasks', array('id' => $project->getId(), 'referer' => $request->request->get('referer'))));
    }

    /**
     * Disable an active project.
     */
    public function disableProjectAction(Project $project)
    {
        $em = $this->getDoctrine()->getManager();

        // Disable project
        $project->setActive(false);
        $em->flush();

        $this->get('session')->getFlashBag()->add('info', $project->getName() . ' a bien été désactivé');

        return $this->redirect($this->generateUrl('act_resource_various_summary'));
    }

    /**
     * Show details project on a page
     * @param  int  $id id of the project
     * @return Response
     */
    public function detailsProjectAction($id)
    {
        //initialize variables
        $sumMetaWorkload = 0;
        $sumAssigned = 0;
        $sumSold = 0;
        $em = $this->getDoctrine()->getManager();
        $resources = array();

        //get the project
        $project = $em->getRepository('ActResourceBundle:Project')->findBy(array('id' => $id));

        //get all metatasks
        $metatasks = $em->getRepository('ActResourceBundle:MetaTask')->getMetaTasksForProject($project[0]);

        //adds metatasks workload
        foreach($metatasks as $metatask){
            $sumMetaWorkload = $sumMetaWorkload + $metatask->getSumWorkloadAssigned();
        }

        //get all tasks
        $tasks = $em->getRepository('ActResourceBundle:Task')->findBy(array('project' => $id));

        //adds tasks workload
        foreach ($tasks as $task){
            $sumSold = $sumSold + $task->getWorkloadSold();
        }

        //create an array with all the resources assignments
        foreach ($project[0]->getAssignments() as $assignment){
            $sumAssigned = $sumAssigned + $assignment->getWorkload();
            $id=$assignment->getResource()->getId();
            if (!empty($resources[$id])){
                $resources[$id] = array(
                    'resource' => $assignment->getResource()->getName(),
                    'workload' => $resources[$id]['workload'] + $assignment->getWorkload()
                );
            }
            else {
                $resources[$id] = array(
                    'resource' => $assignment->getResource()->getName(),
                    'workload' => $assignment->getWorkload()
                );
            }
        }

        return $this->render('ActResourceBundle:Project:detailsProject.html.twig', array(
            'sumMetaWorkload' => $sumMetaWorkload,
            'resources' => $resources,
            'project' => $project[0]->getName(),
            'sumAssigned' => $sumAssigned,
            'sumSold' => $sumSold
        ));
    }
}
