<?php

namespace Act\ResourceBundle\Services\Project;

use Doctrine\ORM\EntityManager;
use Act\ResourceBundle\Entity\Resource;
use Act\ResourceBundle\Entity\Project;
use Act\ResourceBundle\Models\WeekPlanning;

/**
 * Class WeeklyProjectsManager
 *
 * Manage data to create weekly plannings of projects.
 * Can be used to display information of projects given a week.
 *
 */
class WeeklyProjectsManager
{
    // Dependencies
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Get a list of projects with at least one assignment during given period.
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function getWeekProjects(\DateTime $start, \DateTime $end)
    {
        // Load projects for the current week
        $projects = $this->em->getRepository('ActResourceBundle:Project')->getProjectsWithAtLeastOneAssignment($start, $end);

        return $projects;
    }

    /**
     * Generate the week planning data formatted for display
     *
     * @param Project $project
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return Array
     */
    public function getWeekPlanning(Project $project, \DateTime $start, \DateTime $end)
    {
        $weekPlanning = new WeekPlanning();

        // Generate the planning period
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
        $weekPlanning->setPeriod($period);
        $weekPlanning->setStart($start);

        // Get assignments for the given dates.
        $assignments = $this->em->getRepository('ActResourceBundle:Assignment')->getAssignmentsForProject($project, $start, $end);

        // Format data into an array.
        $data = array();
        $data['project'] = $project;
        $data['assignments'] = $assignments;
        $data['resources'] = array();
        $data['days'] = array();

        foreach ($assignments as $assignment) {
            // Initialize this resource array.
            if (!isset($data['resources'][$assignment->getResource()->getId()])) {
                $data['resources'][$assignment->getResource()->getId()] = array(
                    'resource' => $assignment->getResource(),
                    'assignments' => array(),
                    'days' => array()
                );
            }

            // Add the assignment to the resource assignment list.
            $data['resources'][$assignment->getResource()->getId()]['assignments'][] = $assignment;
        }

        // Format days.
        foreach ($data['resources'] as $id => $resource) {
            foreach ($period as $day) {
                $day->setTime(0, 0, 0);

                if (!isset($data['days'][$day->format('d/m/Y')])) {
                    // Global days array.
                    $data['days'][$day->format('d/m/Y')] = $day;
                }

                // Per resource days array.
                $data['resources'][$id]['days'][$day->format('d/m/Y')] = array(
                    'date' => $day
                );

                foreach ($resource['assignments'] as $assignment) {
                    if ($assignment->getDay()->format('d/m/Y') == $day->format('d/m/Y')) {
                        $data['resources'][$id]['days'][$day->format('d/m/Y')]['assignment'] = $assignment;
                    }
                }
            }
        }

        $weekPlanning->setData($data);

        return $weekPlanning;
    }
}