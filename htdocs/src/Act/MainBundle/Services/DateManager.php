<?php

namespace Act\MainBundle\Services;

class DateManager
{
    private $currentWeekFirstDay;
    private $currentWeekLastDay;

    /**
     * Construit l'objet DateManager
     * Initialise les dates de début et de fin de la semaine courante
     */
    public function __construct()
    {
        $days = $this->findFirstAndLastDaysOfWeek();
        $this->currentWeekFirstDay = $days['start'];
        $this->currentWeekLastDay = $days['end'];
    }

    /**
     * Trouve le premier et le dernier jour ouvré de la semaine
     * NB: premier jour = lundi / dernier jour = vendredi
     * @param  \DateTime            $date
     * @return Array('start','end') of DateTime objects
     */
    public function findFirstAndLastDaysOfWeek(\DateTime $date = null)
    {
        if($date == null)
            $date = new \DateTime('now');

        $date->setTime(0,0,0);
        $end = clone $date;
        $start = clone $date;

        $interval = new \DateInterval('P1D');

        $week = $start->format('W');

        if ($end->format('N') < 5) {
            // jour de semaine : on va jusqu'à vendredi
            while ($end->format('W') == $week && $end->format('N') < 5) {
                $end = $end->add($interval);
            }
        } else {
            // jour de week end : on recule
            while ($end->format('W') == $week && $end->format('N') > 5) {
                $end = $end->sub($interval);
            }
        }

        while ($start->format('W') == $week && $start->format('N') > 1) {
            $start = $start->sub($interval);
        }

        // Set the end date hour to the maximum day hour, because some
        // DateTime comparison may be false otherwise if time is set
        $end->setTime(23,59,59);

        return array('start' => $start, 'end' => $end);
    }

    /**
     * Trouve le premier et le dernier jour du mois
     * @param  \DateTime $date
     * @return array
     */
    public function findFirstAndLastDaysOfMonth(\DateTime $date = null)
    {
        if ($date == null) {
            $date = new \DateTime('now');
        } else {
            $date = clone $date;
        }

        $dates = array();

        $tmp = clone $date;
        $dates['start'] = $tmp->modify('first day of this month');

        $tmp = clone $date;
        $dates['end'] = $tmp->modify('last day of this month');
        $dates['end']->setTime(23, 59, 59);

        return $dates;
    }

    /**
     * Trouve la date correpondant à une année et une semaine donnée
     * @param  int       $week
     * @param  int       $year
     * @return \DateTime ou false si erreur
     */
    public function findDateWithYearAndWeek($week, $year)
    {
        $date = new \DateTime();
        $date->setISODate($year, $week);
        $date->setTime(0,0,0);

        return $date;
    }

    /**
     * Compte le nombre de jours ouvrés entre deux dates
     * @param  \DateTime $d1
     * @param  \DateTime $d2
     * @return int
     */
    public function getNbWorkingDaysBetween(\DateTime $d1, \DateTime $d2)
    {
        if ($d1 < $d2) {
            $start = clone $d1;
            $end = clone $d2;
        } else {
            $start = clone $d2;
            $end = clone $d1;
        }

        // Add 1 day to the end date to include it into the DatePeriod
        $end->modify('+1 day');

        $nbdays = 0;
        $interval = \DateInterval::createFromDateString('1 day');
        $period = new \DatePeriod($start, $interval, $end);

        foreach ($period as $day) {
            if ($day->format('N') < 6) {
                $nbdays++;
            }
        }

        return $nbdays;
    }

    /**
     * Détermine si une date donnée se trouve dans la semaine courante
     * @return boolean
     */
    public function belongsToCurrentWeek(\DateTime $d1)
    {
        return ($this->currentWeekFirstDay <= $d1 && $this->currentWeekLastDay >= $d1);
    }

    /**
     * Détermine si une année est bissextile ou non
     * @return boolean
     */
    public function isLeap($year)
    {
        $res = '';
        if ($year % 4 == 0 && $year% 100 != 0 || $year % 400 == 0) {
            $res = true;
        } else {
            $res = false;
        }

        return $res;
    }
}
