<?php

namespace Act\ResourceBundle\Services\Project;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Act\ResourceBundle\Entity\Team;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class TimeLineManager
 *
 * Manage the creation of the data needed to generate
 * a project timeline using smile widget.
 *
 */
class TimeLineManager
{
    /* Dependency */
    private $router;
    private $em;
    private $translator;

    public function __construct(EntityManager $em, Router $router, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->router = $router;
        $this->translator = $translator;
    }

    /**
     * Format the data to display a timeline with next team projects
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return string
     *   A JSON string
     */
    public function generateTeamTimeLineData(\DateTime $start, \DateTime $end)
    {
        // Load projects in these dates
        $projects = $this->em->getRepository('ActResourceBundle:Project')->getProjectsWithAtLeastOneAssignment($start, $end);

        // Data : JSON Format
        $json = array();
        $json['events'] = array();

        foreach ($projects as $project) {
            $element = array();
            $element['title'] = $project['name'];
            $element['start'] = $project['start']->format('r');
            $element['end'] = $project['end']->setTime('23','59','59')->format('r');
            $element['link'] = $this->router->generate('act_resource_project_show', array('id' => $project['id']));
            $element['color'] = $project['color'];

            $description = 'Du ' . $project['start']->format('d/m/Y') . ' au ' . $project['end']->format('d/m/Y') . '<br/>';
            $description .= 'Cette semaine :  ' . $project['total'] . ' jour(s) affecté(s), ' . $project['resources'] . ' ressource(s) impliquée(s)';

            $element['description'] = $description;

            $json['events'][] = $element;
        }

        return json_encode($json, JSON_HEX_QUOT | JSON_HEX_TAG);
    }
}
