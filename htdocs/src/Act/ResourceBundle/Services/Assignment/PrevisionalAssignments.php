<?php

namespace Act\ResourceBundle\Services\Assignment;

use Doctrine\ORM\EntityManager;
use Act\MainBundle\Services\DateManager;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * This class contains code to get the data to render
 * the previsional assignments for one or more teams,
 * for a given week and year.
 * By default, it will show the logged in user's team and the current week.
 */
class PrevisionalAssignments
{
    private $tm;
    private $em;
    private $sc;
    private $request;

    /**
     * @var array projects to display
     */
    private $projects;

    /**
     * @var array teams to display
     */
    private $teams;

    /**
     * @var integer the week to display
     */
    private $week;

    /**
     * @var integer the year to display
     */
    private $year;

    /**
     * @var \DateTime the starting date of the week
     */
    private $start;

    /**
     * @var \DateTime the ending date of the week
     */
    private $end;

    /**
     * @var array the bankholidays on the period
     */
    private $bankholidays;

    /**
     * @var integer the filter to use
     */
    private $filter;

    /**
     * Create a new Previsional Assignment object
     */
    public function __construct(DateManager $dateManager, EntityManager $em, SecurityContext $sc, Request $request, RouterInterface $router)
    {
        $this->tm = $dateManager;
        $this->em = $em;
        $this->sc = $sc;
        $this->request = $request;
        $this->router = $router;

        $this->projects = array();
        $this->teams = array();

        // By default, take the logged in user team
        if ($this->sc->getToken() != null && $this->sc->getToken()->getUser() != null) {
            $user = $this->sc->getToken()->getUser();
            if ($user->getResource() != null) {
                $this->teams = array($user->getResource()->getTeam());
            }
        }

        // By default, take current week and year
        $this->initializeDates();

        // Initialize with request
        $this->initializeWithRequest();

        $this->bankholidays = $this->em->getRepository('ActResourceBundle:BankHoliday')->getBankHolidaysWithLocations($this->start, $this->end);
        foreach ($this->teams as $team) {
            // Load team resources with their assignments efficiently
            $team->setResources($this->em->getRepository('ActResourceBundle:Resource')->getResourcesForThisTeamWithAssignments($team, $this->start, $this->end, $this->projects));
        }
    }

    private function initializeDates()
    {
        $now = new \DateTime('now');
        $this->setWeek($now->format('W'));
        $this->setYear($now->format('Y'));
    }

    /**
     * Initialize the object with GET parameters
     */
    public function initializeWithRequest()
    {
        $teams = $this->request->query->get('teams');
        if ($teams != null) {
            $this->setTeams($this->em->getRepository('ActResourceBundle:Team')->findBy(array('id' => $teams)));
        }

        $week = $this->request->query->get('week');
        if ($week != null) {
            $this->setWeek($week);
        }

        $year = $this->request->query->get('year');
        if ($year != null) {
            $this->setYear($year);
        }

        $filter = $this->request->query->get('filter');
        $projects = $this->request->query->get('projects');
        if ($filter != null) {
            if ($filter == 1) {
                // Loading current user week projects
                $user = $this->sc->getToken()->getUser();
                $this->filter = 1;
                if ($user->getResource()) {
                    $this->projects = $this->em->getRepository('ActResourceBundle:Project')->getResourceProjects($user->getResource(), $this->getStart(), $this->getEnd());
                }
            } elseif ($filter == 2) {
                // Loading only asked projects
                $this->filter = 2;
                $this->projects = $this->em->getRepository('ActResourceBundle:Project')->findBy(array('id' => $projects));
            }
        }
    }

    public function getProjects()
    {
        return $this->projects;
    }

    public function getProjectsIds()
    {
        $ids = array();

        foreach ($this->projects as $project) {
            $ids[] = $project->getId();
        }

        return $ids;
    }

    public function setTeams(array $teams)
    {
        $this->teams = $teams;
    }

    public function getTeams()
    {
        return $this->teams;
    }

    public function getTeamsIds()
    {
        $ids = array();

        foreach ($this->teams as $team) {
            $ids[] = $team->getId();
        }

        return $ids;
    }

    public function setYear($year)
    {
        $this->year = $year;
        $this->getDates();
    }

    public function getYear()
    {
        return $this->year;
    }

    public function setWeek($week)
    {
        $this->week = $week;
        $this->getDates();
    }

    public function getWeek()
    {
        return $this->week;
    }

    public function getStart()
    {
        return clone $this->start;
    }

    public function getEnd()
    {
        return clone $this->end;
    }

    private function getDates()
    {
        $date = $this->tm->findDateWithYearAndWeek($this->week, $this->year);
        $dates = $this->tm->findFirstAndLastDaysOfWeek($date);
        $this->start = $dates['start'];
        $this->end = $dates['end'];
    }

    public function getPeriod()
    {
        $end = clone $this->end;
        $end->modify('+1 day'); // To include the last day

        return new \DatePeriod($this->start, new \DateInterval('P1D'), $end);
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function getBankholidays(\DateTime $date, $location)
    {
        $bankholidays = array();

        foreach ($this->bankholidays as $bk) {
            if ($bk->getDay()->format('d/m/Y') == $date->format('d/m/Y')) {
                $locations = $bk->getLocations();
                foreach ($locations as $loc) {
                    if ($loc->getId() == $location->getId()) {
                        $bankholidays[] = $bk; break;
                    }
                }
            }
        }

        return $bankholidays;
    }

    private function getURL($year, $week)
    {
        $url = $this->router->generate('act_resource_assignment_previsional');
        $url .= '?year='.$year;
        $url .= '&week='.$week;
        foreach ($this->getTeamsIds() as $tid) {
            $url .= '&teams[]='.$tid;
        }
        foreach ($this->getProjectsIds() as $pid) {
            $url .= '&projects[]='.$pid;
        }

        return $url;
    }

    public function getNextURL()
    {
        $date = $this->getEnd();
        $date->modify('+ 7 days');

        return $this->getURL($date->format('Y'), $date->format('W'));
    }

    public function getPrevURL()
    {
        $date = $this->getEnd();
        $date->modify('- 7 days');

        return $this->getURL($date->format('Y'), $date->format('W'));
    }

    public function getResetURL()
    {
        $now = new \DateTime("now");

        return $this->getURL($now->format('Y'), $now->format('W'));
    }
}
