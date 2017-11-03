<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;

class YearSummaryController extends ContainerAware
{
    /**
     * Affichage du récapitulatif de l'utilisation des ressources sur l'année
     */
    public function showAction()
    {
        $yearSummary = $this->container->get('act_resource.year_summary');

        return $this->container->get('templating')->renderResponse('ActResourceBundle:YearSummary:show.html.twig', array(
            'summary' => $yearSummary
        ));
    }
}
