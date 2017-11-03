<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Act\ResourceBundle\Entity\Simulation;

/**
 * Controleur de gestion de la simulation
 *
 */
class SimulationController extends Controller
{

    public function beginAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $simulation = $this->getDoctrine()->getRepository('ActResourceBundle:Simulation')->findAll();

        if (empty($simulation) && $this->get('security.context')->isGranted('ROLE_ADMIN')) {

            $simulatedAssignments = $this->getDoctrine()->getRepository('ActResourceBundle:SimulatedAssignment')->findAll();
               foreach ($simulatedAssignments as $sim) {
                    $em->remove($sim);
            }

            $newSimulation = new Simulation();
            $newSimulation->setUser($this->get('security.context')->getToken()->getUser());
            $newSimulation->setStart(new \DateTime());
            $em->persist($newSimulation);
            $em->flush();
        }

        $referer = $request->headers->get('referer');
        if (!$referer) {
            return $this->redirect($this->generateUrl('act_resource_home'));
        } else {
            return $this->redirect($referer);
        }
    }

    public function rollbackAction(Request $request)
    {
        $referer = $request->headers->get('referer');
        $this->get('act_resource.simulation')->rollback();

        return $this->redirect($referer);
    }

    public function commitAction(Request $request)
    {
        $referer = $request->headers->get('referer');
        $this->get('act_resource.simulation')->commit();

        return $this->redirect($referer);
    }
}
