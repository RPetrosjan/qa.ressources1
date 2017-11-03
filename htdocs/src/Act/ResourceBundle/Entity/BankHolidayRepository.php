<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Bankholiday Entity Repository
 *
 * Contains all queries to gather a collection of
 * bankholidays from the database.
 */
class BankHolidayRepository extends EntityRepository
{
    /**
     * Get bankholidays with their locations.
     * If the start date and/or end date is/are given, it is used to restrict the number of results.
     *
     * @param \DateTime $start restrict by start date
     * @param \DateTime $end   restrict by end date
     * @param Location  $l     restrict by location
     *
     * @return Array
     */
    public function getBankHolidaysWithLocations(\DateTime $start = null, \DateTime $end = null, Location $l = null)
    {
        $qb = $this->createQueryBuilder('a')
                ->join('a.locations', 'l')
                ->addSelect('l');

        if ($start != null && $end != null) {
            $qb->andWhere('a.start >= :start', 'a.start <= :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        } elseif ($start != null) {
            $qb->andWhere('a.start >= :start')->setParameter('start', $start);
        } elseif ($end != null) {
            $qb->andWhere('a.start <= :end')->setParameter('end', $end);
        }

        if ($l != null) {
            $qb->andWhere('l = :location')->setParameter('location', $l);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the total number of bankholidays during the given dates and for the given location.
     * Returns the sum of bankholidays during the working days.
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @param Location  $l
     *
     * @return int
     */
    public function getTotalDuringWorkingDays(\DateTime $start, \DateTime $end, Location $l)
    {
        $qb = $this->createQueryBuilder('bankholiday')
            ->join('bankholiday.locations', 'location')
            ->addSelect('location')
            ->andWhere('location = :location')
            ->andWhere('bankholiday.start >= :start', 'bankholiday.start <= :end')
            ->setParameter('location', $l)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
        ;

        $bankholidays = $qb->getQuery()->getResult();
        $total = 0;
        foreach ($bankholidays as $bk) {
            if ($bk->isWorkingDay()) {
                $total++;
            }
        }

        return $total;
    }
}
