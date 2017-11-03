<?php

namespace Act\ResourceBundle\Controller;

use Act\ResourceBundle\Entity\Project;
use Act\ResourceBundle\Entity\CommonTask;
use Act\ResourceBundle\Entity\MetaTask;
use Act\ResourceBundle\Entity\SubTask;
use Act\ResourceBundle\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class SwiftCutController
 *
 * Code to cut and generate many tasks at once.
 * For example, cut a task into many sub-tasks.
 *
 * @package Act\ResourceBundle\Controller
 */
class SwiftCutController extends Controller
{
    /**
     * Display a page to create many tasks at once.
     *
     * @Security("has_role('ROLE_RP')")
     * @Route("/task/generate/{project}/{task}", name="act_resource_task_generate", requirements={"project" = "\d+", "task" = "\d+"}, defaults={"task" = null})
     */
    public function generateAction(Request $request, Project $project, $task = null)
    {
        $em = $this->getDoctrine()->getManager();
        $referer = $this->get('act_main.referer.manager')->getReferer('act_resource_project_tasks', array('id' => $project->getId()));

        if (!is_null($task) && $task instanceof SubTask) {
            // Check that the parent task is not a sub-task that can't be cut.
            $this->get('session')->getFlashBag()->add('info', 'Action indisponible pour les sous-tâches');
            return $this->redirect($referer);
        }

        if ($request->getMethod() == 'POST') {
            $names          = $request->request->get('name');
            $starts         = $request->request->get('start');
            $ends           = $request->request->get('end');
            $workloads      = $request->request->get('workloadsold');
            $teamOrprofiles = $request->request->get('teamprofiles');
            $i              = 1;

            //get the current task by id
            $currentTask = $this->getDoctrine()
                ->getRepository('ActResourceBundle:Task')
                ->find($task);

            if (is_array($names) && count($names) > 0) {
                foreach ($names as $index => $name) {
                    $newTask = new MetaTask();
                    if (!is_null($task)) {
                        if ($currentTask instanceof MetaTask) {
                            $newTask = new CommonTask();
                            $newTask->setMetatask($currentTask);
                        } elseif ($currentTask instanceof CommonTask) {
                            $newTask = new SubTask();
                            $newTask->setCommontask($currentTask);
                        }
                    }

                    $newTask->setName($name);
                    $newTask->setProject($project);

                    // Allow dates in two formats : d/m/y and d/m/Y
                    if (preg_match("#[0-9]{2}/[0-9]{2}/[0-9]{4}#", $starts[$index])) {
                        $newTask->setStart(\DateTime::createFromFormat('d/m/Y', $starts[$index]));
                    } else {
                        $newTask->setStart(\DateTime::createFromFormat('d/m/y', $starts[$index]));
                    }

                    if (preg_match("#[0-9]{2}/[0-9]{2}/[0-9]{4}#", $ends[$index])) {
                        $newTask->setEnd(\DateTime::createFromFormat('d/m/Y', $ends[$index]));
                    } else {
                        $newTask->setEnd(\DateTime::createFromFormat('d/m/y', $ends[$index]));
                    }

                    // Transform "," into "."
                    if (strpos($workloads[$index], ',') != 0) {
                        $workloads[$index] = str_replace(',', '.', $workloads[$index]);
                    }

                    $newTask->setWorkloadSold($workloads[$index]);

                    // Process teams and profiles
                    if (isset($teamOrprofiles[$i]) && is_array($teamOrprofiles[$i]) && count($teamOrprofiles[$i]) > 0) {
                        foreach ($teamOrprofiles[$i] as $teamOrprofile) {
                            if (substr($teamOrprofile, 0, 5) == 'team-') {
                                // Team
                                $team = $em->getRepository('ActResourceBundle:Team')->find(substr($teamOrprofile, 5));
                                if ($team) {
                                    $newTask->addTeam($team);
                                }
                            } elseif (substr($teamOrprofile, 0, 8) == 'profile-') {
                                // Profile
                                $teamprofile = $em->getRepository('ActResourceBundle:TeamProfile')->find(substr($teamOrprofile, 8));
                                if ($teamprofile) {
                                    $newTask->addTeamprofile($teamprofile);
                                }
                            }
                        }
                    }

                    $em->persist($newTask);
                    $i++;
                }

                $em->flush();

                // Add flash message and redirect to referer
                $this->addFlash('success', $this->get('translator')->trans('tasks.created'));

                return $this->redirect($referer);
            }
        }

        // Predefined tasks
        $predefined = array();
        if (!is_null($task) && $task instanceof CommonTask) {
            $predefined = array('Développement', 'Recette Technique', 'Fine-Tuning', 'Livraison');
        }
        //$task =  (int)$task;
        // Display the form
        return $this->render('ActResourceBundle:SwiftCut:generate.html.twig', array(
            'teams'      => $em->getRepository('ActResourceBundle:Team')->getTeamsWithProfiles(),
            'project'    => $project,
            'task'       => intval($task),
            'referer'    => $referer,
            'predefined' => $predefined
        ));
    }
}
