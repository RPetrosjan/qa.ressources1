<?php

namespace Act\MainBundle\Services;

/**
 * Class TimeManager
 *
 * Contains useful code to convert day fractions
 * into human readable values (0,3j => 2h).
 *
 * @package Act\MainBundle\Services
 */
class TimeManager
{
    /**
     * Returns human readable value of a workload in day fraction.
     * By default, a day consists in 7 hours.
     *
     * @param float|int $workload
     *   The workload in day fraction.
     * @param float|int $hoursPerDays
     *   The number of hours to consider for one day.
     * @param boolean $approximate
     *   Approximate to the closest quarter if wanted.
     *
     * @return string The workload in hours.
     *   The workload in hours.
     */
    public function workloadFormat($workload, $hoursPerDays = 7, $approximate = true)
    {
        // Compute hours.
        $hours = $workload * $hoursPerDays;

        // Get hour and minutes.
        $hour = floor($hours);
        $minutes = round($hours - floor($hours), 2) * 60;

        // Approximate to the closest quarter.
        if ($approximate) {
            if ($minutes < 8) {
                $minutes = 0;
            } elseif ($minutes >= 8 && $minutes < 23) {
                $minutes = 15;
            } elseif ($minutes >= 23 && $minutes < 38) {
                $minutes = 30;
            } elseif ($minutes >= 38 && $minutes < 53) {
                $minutes = 45;
            } elseif ($minutes >= 53) {
                $minutes = 0;
                $hour++;
            }
        }

        // Format into a string.
        if ($hour == 0) {
            $result = $minutes.'min';
        } elseif ($minutes == 0) {
            $result = $hour.'h';
        } else {
            // Leading zero.
            if ($minutes < 10) {
                $minutes = '0' . $minutes;
            }

            $result = $hour.'h'.$minutes;
        }

        return $result;
    }
}
