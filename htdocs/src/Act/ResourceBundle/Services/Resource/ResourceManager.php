<?php

namespace Act\ResourceBundle\Services\Resource;

use Act\ResourceBundle\Entity\Resource;
use Doctrine\ORM\EntityManager;
use Act\MainBundle\Services\DateManager;

/**
 * Class ResourceManager
 *
 * Contains useful methods to manager resources entities
 */
class ResourceManager
{
    private $em;
    private $tm;
    private $bankHolidaysPool = null;

    /**
     * Construct a new ResourceManager
     * Inject the dependencies
     */
    public function __construct(EntityManager $em, DateManager $tm)
    {
        $this->em = $em;
        $this->tm = $tm;
    }

    /**
     * Custom treatments before removing a resource
     * @param \Act\ResourceBundle\Entity\Resource $resource
     */
    public function removeResource(Resource $resource)
    {
        // Remove this CPF from all projects
        $projects = $this->em->getRepository('ActResourceBundle:Project')->getProjectOfCpf($resource);
        foreach ($projects as $project) {
            $project->setCpf(null);
        }

        // Remove this CPT from all projects
        $projectsCPT = $this->em->getRepository('ActResourceBundle:ProjectCpt')->findBy(array('resource' => $resource));
        foreach ($projectsCPT as $projectCPT) {
            $this->em->remove($projectCPT);
        }

        // Remove resource from team object
        $resource->getTeam()->removeResource($resource);

        // Delete resource from database
        $this->em->remove($resource);
        $this->em->flush();
    }

    /**
     * Get the total workload for a given resource and date period.
     * Take into account the incomplete weeks when the resources start and end.
     * Take also into account any bankholidays during this period.
     *
     * @param \Act\ResourceBundle\Entity\Resource $resource
     * @param \DateTime                           $start
     * @param \DateTime                           $end
     * @param bool                                $useBankHolidays must use or not the pool of bankholiday (when executing many time this function)
     *
     * @return float
     */
    public function getResourceWorkload(Resource $resource, \DateTime $start, \DateTime $end, $useBankHolidays = false)
    {
        $workload = 0;

        // Basic check for the dates validity
        // Rescale the start and end dates to the resources arrival and leaving dates if necessary
        if ($resource->getStart() > $start) {
            $start = $resource->getStart();
        }

        if ($end < $start || $start > $end) {
            return $workload;
        }

        if ($resource->getEnd() != null && $resource->getEnd() < $end) {
            $end = $resource->getEnd();
            if ($end < $start) {
                return 0;
            }
        }

        // Compute the total number of working days during that period
        $workingDays = $this->tm->getNbWorkingDaysBetween($start, $end);

        // Check if there are incomplete weeks at the beginning or end of the period
        $nbWeeks = floor($workingDays / 5); // The real number of plain weeks
        $nbWeeksModulo = $workingDays % 5;  // The number of days out of plain weeks
        if ($nbWeeksModulo != 0) {
            // Add the minimum between the number of days for the incomplete week and the days per week
            // working by the given resource
            $workload += min($resource->getDaysPerWeek(), $nbWeeksModulo);
        }
        // Add the number of week multiplied by the resources number of days worked per week
        $workload += $nbWeeks * $resource->getDaysPerWeek();

        // We just need to remove the bank holidays from this workload
        // Load the number of bank holidays during working days for the date period and location
        if ($useBankHolidays) {
            $workload -= $this->checkBankHolidays($resource, $start, $end);
        } else {
            $workload -= $this->em->getRepository('ActResourceBundle:BankHoliday')->getTotalDuringWorkingDays($start, $end, $resource->getLocation());
        }

        if ($workload < 0) {
            $workload = 0;
        }

        return $workload;
    }

    /**
     * Get the available workload for a given resource and date period.
     * Take into account the incomplete weeks when the resources start and end.
     * Take also into account any bankholidays during this period.
     *
     * @param \Act\ResourceBundle\Entity\Resource $resource
     * @param \DateTime                           $start
     * @param \DateTime                           $end
     * @param bool                                $useBankHolidays must use or not the pool of bankholiday (when executing many time this function)
     *
     * @return float
     */
    public function getResourceWorkloadAvailable(Resource $resource, \DateTime $start, \DateTime $end, $useBankHolidays = false)
    {
        $workload = 0;

        // Basic check for the dates validity
        // Rescale the start and end dates to the resources arrival and leaving dates if necessary
        if ($resource->getStart() > $start) {
            $start = $resource->getStart();
        }

        if ($end < $start || $start > $end) {
            return $workload;
        }

        if ($resource->getEnd() != null && $resource->getEnd() < $end) {
            $end = $resource->getEnd();
            if ($end < $start) {
                return 0;
            }
        }

        // Get the total workload
        if ($useBankHolidays) {
            $workload = $this->getResourceWorkload($resource, $start, $end, true);
        } else {
            $workload = $this->getResourceWorkload($resource, $start, $end);
        }

        // And then, remove all the assignments found in these dates
        $assignmentWorkload = $this->em->getRepository('ActResourceBundle:Assignment')->getAssignmentsSumForResource($start, $end, $resource);
        $workload -= $assignmentWorkload;

        return $workload;
    }

    /**
     * Get the available workload for a given resource and week
     *
     * @param \Act\ResourceBundle\Entity\Resource $resource
     * @param int                                 $week
     * @param int                                 $year
     *
     * @return float
     */
    public function getWeekWorkloadAvailable(Resource $resource, $week, $year)
    {
        // Find week starting and ending dates
        $date = $this->tm->findDateWithYearAndWeek($week, $year);
        $dates = $this->tm->findFirstAndLastDaysOfWeek($date);

        return $this->getResourceWorkloadAvailable($resource, $dates['start'], $dates['end']);
    }

    /**
     * Use a pool of loaded bankholidays for better performances if possible.
     * Returns the number of bankholidays for the given resource between the two dates.
     *
     * @return int
     */
    public function checkBankHolidays(Resource $resource, \DateTime $start, \DateTime $end)
    {
        $nbDaysFree = 0;

        if (is_null($this->bankHolidaysPool)) {
            // Initialize pool if needed
            $this->initializeBankHolidaysPool($start, $end);
        }

        if (count($this->bankHolidaysPool) > 0) {
            foreach ($this->bankHolidaysPool as $holiday) {
                if ($holiday->getStart() >= $start && $holiday->getStart() <= $end) {
                    foreach ($holiday->getLocations() as $location) {
                        if ($location->getName() == $resource->getLocation()) {
                            $nbDaysFree++;
                            break;
                        }
                    }
                }
            }
        }

        return $nbDaysFree;
    }

    /**
     * Return the bankholiday pool
     *
     * @return array
     */
    public function getBankHolidaysPool()
    {
        return $this->bankHolidaysPool;
    }

    /**
     * Initialize the bankholiday pool
     * with bankholidays between given dates
     *
     * @param \DateTime $start
     * @param \DateTime $end
     */
    public function initializeBankHolidaysPool(\DateTime $start, \DateTime $end)
    {
        $this->bankHolidaysPool = $this->em->getRepository('ActResourceBundle:BankHoliday')->getBankHolidaysWithLocations($start, $end);
    }

    /**
     * Get the total workload available for the resource in the given period.
     *
     * The availability can be computed like this :
     * Availability = days_per_week - bankholidays
     *
     * @param  \Act\ResourceBundle\Entity\Resource|Resource $resource
     * @param  \DateTime                                    $start
     * @param  \DateTime                                    $end
     * @param  bool                                         $useBankHolidays
     *
     * @return float|int
     */
    public function getResourceTotalWorkloadAvailable(Resource $resource, \DateTime $start, \DateTime $end, $useBankHolidays = false)
    {
        $workload = 0;

        // Basic check for the dates validity
        // Rescale the start and end dates to the resources arrival and leaving dates if necessary
        if ($resource->getStart() > $start) {
            $start = $resource->getStart();
        }

        if ($end < $start || $start > $end) {
            return $workload;
        }

        if ($resource->getEnd() != null && $resource->getEnd() < $end) {
            $end = $resource->getEnd();
            if ($end < $start) {
                return 0;
            }
        }

        // Get the total workload
        return $this->getResourceWorkload($resource, $start, $end, $useBankHolidays);
    }

}
