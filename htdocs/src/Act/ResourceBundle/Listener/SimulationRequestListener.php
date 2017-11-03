<?php

namespace Act\ResourceBundle\Listener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class SimulationRequestListener
 *
 * Add flash messages to inform users when a simulation is taking place.
 * Protect some pages from being accessed by users if not owners of the simulation.
 *
 * @package Act\ResourceBundle\Listener
 */
class SimulationRequestListener
{
    private $em;
    private $router;
    private $sc;
    private $translator;

    public function __construct($em, $router, $sc, $translator)
    {
        $this->em = $em;
        $this->router = $router;
        $this->sc = $sc;
        $this->translator = $translator;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // Only check on master request
        if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
            return;
        }

        // Routes not allowed during simulation
        $routesToBlock = array(
            'act_resource_project_show',
            'admin_act_resource_project_edit',
            'admin_act_resource_project_delete',
            'act_resource_project_tasks',
            'act_resource_project_generate_meta',
            'act_resource_task_generate',
            'act_resource_resource_list',
            'act_resource_resource_show',
            'act_resource_resource_edit',
            'act_resource_resource_delete',
            'act_resource_project_add_assignment_ajax',
            'act_resource_project_save_sortable'
        );

        $currentRoute = $event->getRequest()->attributes->get('_route');
        $simulation = $this->em->getRepository('ActResourceBundle:Simulation')->findAll();

        // Add some flash messages to inform users
        if (!empty($simulation)) {
            if ($this->sc->getToken() != null && is_object($this->sc->getToken()->getUser()) &&
              ($this->sc->getToken()->getUser()->getId() == $simulation[0]->getUser()->getId())) {
                // If current user is the owner of the simulation, show rollback/commit buttons
                $event->getRequest()->getSession()->getFlashBag()->set(
                  'info',
                  $this->translator->trans('simulation.running').'
                   <hr />
                   <a class="btn btn-success" href="'.$this->router->generate('act_resource_commit_simulation').'">Commit</a>
                   <a class="btn btn-danger" href="'.$this->router->generate('act_resource_rollback_simulation').'">Rollback</a>'
                );
            } else {
                // If not current user, just show a notification
                $event->getRequest()->getSession()->getFlashBag()->set(
                    'info',
                    $this->translator->trans('simulation.running').' : '.$simulation[0]->getUser()->getUsername().' le '.$simulation[0]->getStart()->format('d-m-Y à H:i')
                );
            }

            if (in_array($currentRoute, $routesToBlock)) {
                // If the page is not allowed during simulation
                $usr = $this->sc->getToken()->getUser();

                if ($usr->getId() != $simulation[0]->getUser()->getId()) {
                    $url = $this->router->generate('act_resource_home');
                    $event->getRequest()->getSession()->getFlashBag()->add('warning', $this->translator->trans('access.forbiden.simulation'));
                    $event->setResponse(new RedirectResponse($url, 302));
                }
            }
        }
    }
}
