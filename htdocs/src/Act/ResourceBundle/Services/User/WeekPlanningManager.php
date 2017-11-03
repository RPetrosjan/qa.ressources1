<?php

namespace Act\ResourceBundle\Services\User;

use Doctrine\ORM\EntityManager;
use Act\MainBundle\Services\DateManager;
use Act\ResourceBundle\Entity\Resource;
use Act\ResourceBundle\Models\WeekPlanning;

/**
 * Class WeekPlanningManager
 *
 * Manage data to create week planning for users
 *
 */
class WeekPlanningManager
{
    // Dependencies
    private $em;
    private $dateManager;

    public function __construct(EntityManager $em, DateManager $tm)
    {
        $this->em = $em;
        $this->dateManager = $tm;
    }

    /**
     * Generate the week planning data formatted for display
     * @param \Act\ResourceBundle\Entity\Resource $resource
     * @param \DateTime $start
     * @param \DateTime $end
     * @return Array
     */
    public function getWeekPlanning(Resource $resource, \DateTime $start = null, \DateTime $end = null)
    {
        $weekPlanning = new WeekPlanning();
        $weekPlanning->setResource($resource);

        // Generate the planning period
        if (is_null($start) or is_null($end)) {
            $dates = $this->dateManager->findFirstAndLastDaysOfWeek();
            $start = $dates['start'];
            $end = $dates['end'];
            $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
        } else {
            $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
        }

        $weekPlanning->setPeriod($period);
        $weekPlanning->setStart($start);

        // Load projects for the current week
        $weekProjects = $this->em->getRepository('ActResourceBundle:Project')->getProjectsOfResource($resource, $start, $end);

        // Load bankholidays for the current week
        $bankholidays = $this->em->getRepository('ActResourceBundle:BankHoliday')->getBankHolidaysWithLocations($start, $end, $resource->getLocation());

        // Format data into an array
        $projects = array();
        foreach ($weekProjects as $project) {
            $projects[$project->getId()] = array(
              'project' => $project,
              'days' => array()
            );

            foreach ($period as $day) {
                $day->setTime(0,0,0);
                $projects[$project->getId()]['days'][$day->format('d/m/Y')] = array(
                    'date' => $day,
                    'assignments' => array()
                );

                foreach ($project->getAssignments() as $assignment) {
                    if ($assignment->getDay() == $day) {
                        $projects[$project->getId()]['days'][$day->format('d/m/Y')]['assignment'] = $assignment;  break;
                    }
                }

                foreach ($bankholidays as $bankholiday) {
                    if ($bankholiday->getDay() == $day) {
                        $projects[$project->getId()]['days'][$day->format('d/m/Y')]['bankholidays'][] = $bankholiday;
                    }
                }
            }
        }

        $weekPlanning->setProjects($projects);

        return $weekPlanning;
    }
}
