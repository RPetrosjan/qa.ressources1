<?php
// src/Act/ResourceBundle/Twig/ResourceExtension.php

namespace Act\ResourceBundle\Twig;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContext;

class ResourceExtension extends \Twig_Extension
{

    private $em;
    private $sc;

    public function __construct(EntityManager $em, SecurityContext $sc)
    {
        $this->em = $em;
        $this->sc = $sc;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('inSimulation', array($this, 'inSimulationFilter')),
            new \Twig_SimpleFilter('currentUserSimulation',array($this, 'currentUserSimulationFilter')),
        );
    }

    public function inSimulationFilter()
    {
        return 1 == count($this->em->getRepository('ActResourceBundle:Simulation')->findAll());
    }

    public function currentUserSimulationFilter()
    {
        $simulation = $this->em->getRepository('ActResourceBundle:Simulation')->findAll();
        if (!empty($simulation)) {
            return $this->sc->getToken()->getUser() == $simulation[0]->getUser();
        } else {
            return false;
        }

    }

    public function getName()
    {
        return 'resource_extension';
    }

}
