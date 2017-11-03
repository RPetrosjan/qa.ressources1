<?php

namespace Act\ResourceBundle\Services\Summary;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Class VariousSummary
 *
 * Contains functions to get data for the various summary dashboard
 * So data like active projects, idle projects and resources...
 *
 */
class VariousSummary
{
    protected $em;
    protected $stopwatch;

    public function __construct(EntityManager $em, Stopwatch $stopwatch = null)
    {
        $this->em = $em;
        $this->stopwatch = $stopwatch;
    }

    /**
     * Return projects with at least one assignment or task
     * during the given date period
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function summaryProject(\DateTime $start, \DateTime $end)
    {
        // Performance monitoring
        if ($this->stopwatch) {
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        // Load all projects that have assignments or tasks in the given date period
        $data = $this->em->getRepository('ActResourceBundle:Project')->getProjectsWithAtLeastOneAssignmentTask($start, $end);

        // Performance monitoring
        if ($this->stopwatch) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }

        return $data;
    }

    /**
     * Returns resources with at least one assignment
     * during the given date period
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function summaryResources(\DateTime $start, \DateTime $end)
    {
        // Performance monitoring
        if ($this->stopwatch) {
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        // Load all resources with assignments in the given period
        $data = $this->em->getRepository('ActResourceBundle:Resource')->getResourcesWithAssignments($start, $end);

        // Performance monitoring
        if ($this->stopwatch) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }

        return $data;
    }

    /**
     * Returns enabled projects with no assignments and no tasks
     * during the given date period
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function summarySleepingProjects(\DateTime $start, \DateTime $end)
    {
        // Performance monitoring
        if ($this->stopwatch) {
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        $data = $this->em->getRepository('ActResourceBundle:Project')->getIdleProjects($start, $end);

        // Performance monitoring
        if ($this->stopwatch) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }

        return $data;
    }

    /**
     * Returns resources with no assignments
     * during the given date period
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function summarySleepingResources(\DateTime $start, \DateTime $end)
    {
        // Performance monitoring
        if ($this->stopwatch) {
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        $this->em->clear(); // Clear the entity manager because of object caching - not wanted here

        $data = $this->em->getRepository('ActResourceBundle:Resource')->getResourcesWithNoAssignments($start, $end);

        // Performance monitoring
        if ($this->stopwatch) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }

        return $data;
    }

    /**
     * Returns disabled projects with at least one
     * assignment or task during the date period
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function summaryActiveProjectDisabled(\DateTime $start, \DateTime $end)
    {
        // Performance monitoring
        if ($this->stopwatch) {
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        $data = $this->em->getRepository('ActResourceBundle:Project')->getProjectsWithAtLeastOneAssignmentTask($start, $end, 0);

        // Performance monitoring
        if ($this->stopwatch) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }

        return $data;
    }
}
