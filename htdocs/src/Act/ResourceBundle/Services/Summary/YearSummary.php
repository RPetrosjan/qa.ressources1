<?php

namespace Act\ResourceBundle\Services\Summary;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Class YearSummary
 *
 * Manage the formatting of data for
 * the yearly summary of assignments
 *
 */
class YearSummary
{
    /* Dependency */
    private $container;

    /* Request parameters */
    private $startDate;
    private $monthsAfter;
    private $monthsBefore;
    private $inversion = true;

    /* Date interval */
    private $start;
    private $end;
    private $period;

    /* Date data to generate table header */
    private $years;
    private $months;
    private $weeks;
    private $nbWeeks;

    /* Various data */
    private $bankholidays;
    private $teams;

    /* Main data array for the view */
    private $data;

    public function __construct(Container $container)
    {
        $this->container = $container;

        // Performance monitoring
        if ($this->container->has('debug.stopwatch')) {
            $this->stopwatch = $this->container->get('debug.stopwatch');
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        // Initialize object with request parameters
        $this->initializeWithRequest();
        $this->initializeDateInterval();

        // Load data
        $this->loadBankholidays();
        $this->loadTeams();

        // Setup the right order for teams
        $this->orderTeams();

        // Initialize data used by the template to display the summary
        $this->initializeDateData();
        $this->initializeMainData();

        if ($this->stopwatch != null) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }
    }

    /**
     * Load the bankholidays
     */
    private function loadBankholidays()
    {
        if ($this->stopwatch != null) {
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        $this->bankholidays = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:BankHoliday')->getBankHolidaysWithLocations($this->start, $this->end);

        if ($this->stopwatch != null) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }
    }

    /**
     * Load the teams
     */
    private function loadTeams()
    {
        if ($this->stopwatch != null) {
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        $this->teams = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:Team')->getYearSummaryData($this->start, $this->end);

        if ($this->stopwatch != null) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }
    }

    /**
     * Put the teams in the right order, with user
     * team located at the first position
     */
    private function orderTeams()
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        $userTeam = $user->getResource()->getTeam();

        // Put user team at first position
        $reorderedTeams = array($userTeam);
        foreach ($this->teams as $t) {
          if($t->getId() != $userTeam->getId()) $reorderedTeams[] = $t;
        }

        $this->teams = $reorderedTeams;
    }

    /**
     * Get the request parameters to initialize some data
     */
    private function initializeWithRequest()
    {
        if ($this->stopwatch != null) {
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        // Gather request parameters
        $startDate = $this->container->get('request')->request->get('start');
        $monthsAfter = $this->container->get('request')->request->get('monthsafter');
        $monthsBefore = $this->container->get('request')->request->get('monthsbefore');

        // Setting the starting date of the planning
        if ($startDate != null) {
            $startDate = \DateTime::createFromFormat('d/m/Y', $startDate);
        } else {
            $startDate = new \DateTime();
        }

        // Setting the months before/after $startDate to show
        if ($monthsBefore == null) {
            $monthsBefore = $this->container->getParameter('act.recap.year.months_before');
        }
        if ($monthsAfter == null) {
            $monthsAfter = $this->container->getParameter('act.recap.year.months_after');
        }

        $this->startDate = $startDate;
        $this->monthsBefore = $monthsBefore;
        $this->monthsAfter = $monthsAfter;

        if ($this->stopwatch != null) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }
    }

    /**
     * Initialize the date interval to display
     */
    private function initializeDateInterval()
    {
        if ($this->stopwatch != null) {
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        // Get first and last days of central week
        $dates = $this->container->get('act_main.date.manager')->findFirstAndLastDaysOfMonth($this->startDate);

        // Remove $this->monthsBefore to start date
        $this->start = $dates['start'];
        $this->start->sub(new \DateInterval('P'.$this->monthsBefore.'M'));

        // Add $this->monthsAfter to end date
        $this->end = $dates['end'];
        $this->end->add(new \DateInterval('P'.($this->monthsAfter+1).'M'));

        // Now we need to put start on the starting day of the starting week
        $startWeekDates = $this->container->get('act_main.date.manager')->findFirstAndLastDaysOfWeek($this->start);
        $this->start = $startWeekDates['start'];

        // Same for end, we need to put end on the ending day of the ending week
        $endWeekDates = $this->container->get('act_main.date.manager')->findFirstAndLastDaysOfWeek($this->end);
        $this->end = $endWeekDates['end'];

        // Create period
        $this->period = new \DatePeriod($this->start, \DateInterval::createFromDateString('1 week'), $this->end);

        if ($this->stopwatch != null) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }
    }

    /**
     * Generate additional data to avoid redondant computations
     * in templates when displaying the summary
     */
    private function initializeDateData()
    {
        if ($this->stopwatch != null) {
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        // Years array :
        //    Year
        //    - Number of weeks

        // Months array :
        //    Year
        //    - Month
        //      - Number of weeks

        // Weeks array :
        //    Year
        //    - Week
        //      - Start
        //      - End
        //      - Bankholidays
        //        - Location
        //          - Bankholiday

        $now = new \DateTime();
        $currentWeek = $now->format('W');
        $currentYear = $now->format('Y');
        $this->years = array();
        $this->months = array();
        $this->weeks = array();
        $this->nbWeeks = 0;

        // Localized month name
        $locale = $this->container->get('request')->getLocale();
        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::FULL, \IntlDateFormatter::FULL);
        $formatter->setPattern('MMMM');

        foreach ($this->period as $w) {
            $year = $w->format('Y');
            $month = ucfirst($formatter->format($w)); // Localized
            $week = $w->format('W');
            $tmp = $this->container->get('act_main.date.manager')->findFirstAndLastDaysOfWeek($w);
            $wstart = $tmp['start'];
            $wend = $tmp['end'];
            $this->nbWeeks++;

            // Fill the years array
            if (!isset($this->years[$year]['nb'])) {
                $this->years[$year]['nb'] = 1;
            } else {
                $this->years[$year]['nb']++;
            }

            // Fill the months array
            if (!isset($this->months[$year][$month])) {
                $this->months[$year][$month]['nb'] = 1;
            } else {
                $this->months[$year][$month]['nb']++;
            }

            // Fill the weeks array
            if ($week == $currentWeek && $year == $currentYear) {
                $this->weeks[$year][$week]['current'] = true;
            }

            $this->weeks[$year][$week]['start'] = $wstart;
            $this->weeks[$year][$week]['end'] = $wend;

            // Retrieve bankholidays
            $this->weeks[$year][$week]['bankholidays'] = array();
            foreach ($this->bankholidays as $bankholiday) {
                if ($bankholiday->getDay()->format('N') < 6 && $bankholiday->getDay() >= $wstart && $bankholiday->getDay() <= $wend) {
                    foreach ($bankholiday->getLocations() as $location) {
                        if (!isset($this->weeks[$year][$week]['bankholidays'][$location->getName()])) {
                            $this->weeks[$year][$week]['bankholidays'][$location->getName()] = 0;
                        }

                        $this->weeks[$year][$week]['bankholidays'][$location->getName()] += 1;
                    }
                }
            }
        }

        if ($this->stopwatch != null) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }
    }

    /**
     * Generate the main data array with
     * all information to display the summary
     */
    private function initializeMainData()
    {
        if ($this->stopwatch != null) {
            $this->stopwatch->start(__CLASS__.'::'.__FUNCTION__);
        }

        $this->data = array();

        foreach ($this->teams as $team) {
            $this->data[$team->getId()] = array(
                'team' => $team,
                'resources' => array()
            );

            foreach ($team->getResources() as $resource) {
                $this->data[$team->getId()]['resources'][$resource->getId()] = array(
                    'resource' => $resource,
                    'weeks' => array()
                );

                foreach ($this->weeks as $year => $yearData) {
                    foreach ($yearData as $week => $weekData) {
                        $data = array(
                            'assignments' => $resource->getWeekWorkload($weekData['start'], $weekData['end']),
                            'bankholidays' => (isset($weekData['bankholidays'][$resource->getLocation()->getName()]) ? $weekData['bankholidays'][$resource->getLocation()->getName()] : 0),
                            'current' => (isset($weekData['current']) ? true : false),
                            'available' => $resource->isAvailable($weekData['start'], $weekData['end']),
                            'classes' => array(),
                            'week' => $week,
                            'year' => $year,
                            'start' => $weekData['start']
                        );

                        // Compute availability
                        $availability = $resource->getDaysPerWeek() - $data['bankholidays'];
                        $data['load'] = 0;
                        if ($availability > 0) {
                            $data['load'] = floatval(number_format(100 - ((($availability - $data['assignments']) / $availability) * 100), 2));
                        }

                        // Compute workload to display
                        $data['workload'] = $data['assignments'] + $data['bankholidays'];
                        if ($this->inversion) {
                            $data['workload'] = floatval(number_format($resource->getDaysPerWeek() - $data['workload'], 2));
                        }

                        // Define CSS classes to set
                        if ($data['load'] > 100) {
                            $data['classes'][] = 'background-workload-high';
                            $data['classes'][] = 'workload-high';

                        } elseif ($data['load'] == 100) {
                            $data['classes'][] = 'background-workload-ok';
                            $data['classes'][] = 'workload-ok';

                        } elseif ($data['load'] >= 80) {
                            $data['classes'][] = 'background-workload-low';
                            $data['classes'][] = 'workload-low';

                        } elseif ($data['load'] > 0) {
                            $data['classes'][] = 'background-workload-vlow';
                            $data['classes'][] = 'workload-vlow';

                        } else {
                            $data['classes'][] = 'background-workload-xlow';
                            $data['classes'][] = 'workload-xlow';
                        }

                        if ($data['current']) {
                            $data['classes'][] = 'current-week';
                            $data['classes'][] = 'first-of-week';
                            $data['classes'][] = 'last-of-week';
                        }

                        $this->data[$team->getId()]['resources'][$resource->getId()]['weeks'][$year.'_'.$week] = $data;
                    }
                }
            }
        }

        if ($this->stopwatch != null) {
            $this->stopwatch->stop(__CLASS__.'::'.__FUNCTION__);
        }
    }

    /** Getters **/
    public function getYears()
    {
        return $this->years;
    }

    public function getMonths()
    {
        return $this->months;
    }

    public function getWeeks()
    {
        return $this->weeks;
    }

    public function totalWeeks()
    {
        return $this->nbWeeks;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMonthsBefore()
    {
        return $this->monthsBefore;
    }

    public function getMonthsAfter()
    {
        return $this->monthsAfter;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }
}
