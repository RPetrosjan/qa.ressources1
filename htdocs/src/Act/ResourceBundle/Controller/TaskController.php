<?php

namespace Act\ResourceBundle\Controller;

use Act\ResourceBundle\Entity\CommonTask;
use Act\ResourceBundle\Entity\MetaTask;
use Act\ResourceBundle\Entity\Task;
use Act\ResourceBundle\Entity\SubTask;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Controlleur de gestion des tâches
 *
 */
class TaskController extends Controller
{
    /**
     * Suppression d'une tâche en AJAX
     * @return Response
     */
    public function deleteAJAXAction()
    {
        $em = $this->getDoctrine()->getManager();
        $request = $this->get('request');

        // Vérification AJAX
        if(!$request->isXmlHttpRequest())
            throw $this->createNotFoundException($this->get('translator')->trans('only.available.ajax'));

        $task = $em->getRepository('ActResourceBundle:Task')->find($request->request->get('task'));

        // Vérification droits
        $this->checkRights($task);

        $em->remove($task);

        try {
            $em->flush();
            $response = new Response(json_encode(array('success' => $this->get('translator')->trans('deleted.task'))));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        } catch (\Exception $e) {
            $response = new Response(json_encode(array('error' => $this->get('translator')->trans('unable.to.delete.task', array('%message%' => $e->getMessage())))));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
    }

    /**
     * Vérifie que l'utilisateur connecté à les droits sur la tâche donnée
     * @param  Task $task
     * @throws AccessDeniedHttpException
     */
    private function checkRights(Task $task)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $request = $this->getRequest();

        // Vérification des droits
        if ($task instanceof \Act\ResourceBundle\Entity\SubTask) {
            if (!$this->container->get('act.cptRights')->hasAccess($task, $user) && !$this->get('security.context')->isGranted('ROLE_RP')) {
                if (!$request->isXmlHttpRequest()) {
                    throw new AccessDeniedHttpException('Accès uniquement autorisé aux CPT du projet et aux responsables de production');
                } else {
                    $response = new Response(json_encode(array('error' => 'Accès uniquement autorisé aux CPT du projet et aux responsables de production')));
                    $response->headers->set('Content-Type', 'application/json');

                    return $response;
                }
            }

        } elseif ($task instanceof MetaTask || $task instanceof CommonTask) {
            // Seulement les responsables de production
            if (!$this->get('security.context')->isGranted('ROLE_RP')) {
                if (!$request->isXmlHttpRequest()) {
                    throw new AccessDeniedHttpException('Accès limité aux responsables de production');
                } else {
                    $response = new Response(json_encode(array('error' => 'Accès limité aux responsables de production')));
                    $response->headers->set('Content-Type', 'application/json');

                    return $response;
                }
            }
        }
    }
}
