<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class VariousSummaryController
 *
 * Contains code to display an admin dashboard
 * with data dealing with active/inactive projects
 * and resources in order to see which one or still used
 *
 */
class VariousSummaryController extends Controller
{
    /**
     * Display the project/resource admin dashboard
     *
     * @Route("/admin/summary/various", name="act_resource_various_summary")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction(Request $request)
    {
        $summary = $this->container->get('act_resource.various_summary');
        $start   = \DateTime::createFromFormat('d/m/Y', $request->query->get('start'));
        $end     = \DateTime::createFromFormat('d/m/Y', $request->query->get('end'));

        // Check dates
        if ($start == null || $end == null || $start > $end) {
            $start  = new \DateTime('first day of last month');
            $end    = new \DateTime('last day of last month');
        }

        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        // Get all data from the service
        $activeProjects = $summary->summaryProject($start, $end);
        $activeResources = $summary->summaryResources($start, $end);
        $sleepingProjects = $summary->summarySleepingProjects($start, $end);
        $sleepingResources = $summary->summarySleepingResources($start, $end);
        $inactiveProjects = $summary->summaryActiveProjectDisabled($start, $end);

        // Render the page
        return $this->render('ActResourceBundle:VariousSummary:summary.html.twig', array(
            'activeProjects' => $activeProjects,
            'activeResources' => $activeResources,
            'sleepingProjects' => $sleepingProjects,
            'sleepingResources' => $sleepingResources,
            'inactiveProjects' => $inactiveProjects,
            'start' => $start,
            'end' => $end,
        ));
    }
}
