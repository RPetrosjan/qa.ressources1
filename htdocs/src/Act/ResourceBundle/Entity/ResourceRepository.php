<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\EntityRepository;

/**
 * Contient toutes les méthodes pour récupèrer une collection
 * de ressources dans la base de données.
 *
 */
class ResourceRepository extends EntityRepository
{
    /**
     * Récupère les ressources avec leur utilisateur associé, leur lieu et leur équipe
     * Utiliser pour afficher la liste des ressources
     */
    public function getResourcesWithUserLocationTeam()
    {
        $qb = $this->createQueryBuilder('resource')
                ->leftJoin('resource.user', 'user')
                ->join('resource.location', 'location')
                ->join('resource.team', 'team')

                ->addSelect('user')
                ->addSelect('location')
                ->addSelect('team')

                ->addOrderBy('resource.nameShort');

        return $qb->getQuery()->getResult();
    }

      /**
     * Récupère la Ressource grace a son id
     */
    public function getResourcesWithId($id)
    {
        $qb = $this->createQueryBuilder('R')
        ->where('R.id = :id')
        ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère les ressources avec leur équipe
     * triés par nom d'équipe, puis nom de ressource
     */
    public function getResourcesWithTeam()
    {
        $qb = $this->createQueryBuilder('a')
                ->join('a.team', 't')
                ->addSelect('t')
                ->addOrderBy('t.name')
                ->addOrderBy('a.nameShort');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les ressources qui appartiennent à cette équipe
     * @param  int          $id - l'id de l'équipe
     * @return QueryBuilder
     */
    public function getResourcesForThisTeam($id)
    {
        $qb = $this->createQueryBuilder('a')
                ->join('a.team', 't')
                ->where('t.id = :id')
                ->setParameter('id', $id)
                ->orderBy('a.nameShort');

        return $qb;
    }

    /**
     * Récupère toutes les ressources sans utilisateurs liés
     */
    public function getResourcesWithNoUser()
    {
        $qb = $this->createQueryBuilder('r')
                ->join('r.team', 'team')
                ->addSelect('team')
                ->where('r.user IS NULL')
                ->addOrderBy('r.name');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère la ressource, avec ses affectations pour la semaine
     *
     * @param  int       $id
     * @param  \DateTime $dayweek
     * @return Resource
     */
    public function getResourceWithWeekAssignments($id, \DateTime $dayweek = null)
    {
        if($dayweek == null)
            $dayweek = new \DateTime('now');

        $dayweek->setTime(0,0,0);
        $interval = \DateInterval::createFromDateString('1 day');
        $week = $dayweek->format('W');
        $start = clone $dayweek;
        $end = clone $dayweek;

        while ($end->format('W') == $week && $end->format('N') < 6) {
            $end = $end->add($interval);
        }

        while ($start->format('W') == $week && $start->format('N') > 1) {
            $start = $start->sub($interval);
        }

        $qb = $this->createQueryBuilder('r')
                ->leftJoin('r.assignments', 'assignments', Expr\Join::WITH, 'assignments.day >= :start AND assignments.day <= :end')
                ->leftJoin('assignments.project', 'project')

                ->addSelect('assignments')
                ->addSelect('project')

                ->andWhere('project.active = 1')
                ->andWhere('r = :resource')

                ->addOrderBy('project.nameShort')

                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->setParameter('resource', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns resources with assignments during the date period
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function getResourcesWithAssignments(\DateTime $start, \DateTime $end)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT r.id, r.name, SUM(a.workload_assigned) workload, COUNT(a.id) assignments
            FROM ActResourceBundle:Resource r
            INNER JOIN r.assignments a WITH a.day >= :start AND a.day <= :end
            INNER JOIN a.project p
            WHERE p.active = 1
            GROUP BY r.id
            HAVING assignments > 0
            ORDER BY r.name'
        );

        $query->setParameter(':start', $start);
        $query->setParameter(':end', $end);

        return $query->getResult();
    }

    /**
     * Returns resources with no assignments during the date period
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return array
     */
    public function getResourcesWithNoAssignments(\DateTime $start, \DateTime $end)
    {
        // Only get projects with no assignments and no tasks during the time period
        $query = $this->getEntityManager()->createQuery('
            SELECT r.id, r.name
            FROM ActResourceBundle:Resource r
            WHERE NOT EXISTS (
                SELECT a.id
                FROM ActResourceBundle:Assignment a
                WHERE a.resource = r.id
                AND a.day >= :start
                AND a.day <= :end
            )'
        );

        $query->setParameter(':start', $start);
        $query->setParameter(':end', $end);

        return $query->getResult();
    }

    /**
     * Récupère les ressources d'une équipe et leurs affectations et projet
     * on ne prend que les affectations et ressources qui correspondent à la période
     *
     * @param  int       $team_id  l'id de l'équipe à charger
     * @param  \DateTime $start
     * @param  \DateTime $end
     * @param  array     $projects
     * @return Array
     */
    public function getResourcesForThisTeamWithAssignments($team_id, \DateTime $start, \DateTime $end, $projects = array())
    {
        $start->setTime(0,0,0);
        $end->setTime(0,0,0);

        $qb = $this->createQueryBuilder('resource')
                ->leftJoin('resource.assignments', 'assignment', Expr\Join::WITH, 'assignment.day <= :end AND assignment.day >= :start')
                ->addSelect('assignment')

                ->innerJoin('resource.location', 'loc')
                ->addSelect('loc')

                ->andWhere('resource.team = :team')

                ->setParameter('team', $team_id)
                ->setParameter('start', $start)
                ->setParameter('end', $end)

                ->addOrderBy('resource.nameShort')
                ->addOrderBy('assignment.day')
                ->addOrderBy('assignment.created');

        // If there are some projects specified
        if (count($projects) > 0) {
            $qb->leftJoin('assignment.project', 'project', Expr\Join::WITH, 'project.id IN (:projects)')
              ->addSelect('project')
              ->andWhere('project.active = 1')
              ->setParameter('projects', $projects);
        } else {
            $qb->leftJoin('assignment.project', 'project')
              ->addSelect('project')
              ->andWhere('project.active = 1');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Pour le formulaire de création de projet, tri des ressources
     * @return QueryBuilder
     */
    public function getResourcesOrderByNameShort()
    {
        $qb = $this->createQueryBuilder('resource')
                    ->addSelect('resource')
                    ->addOrderBy('resource.nameShort');

        return $qb;
    }

    /**
     * Retrieve resources that had their week assignments created or updated
     * after the given date and within the period only.
     * NB: only resources with linked Users
     *
     * @param \DateTime $date the date to check update/create time
     * @param \DateTime $end  the date to limit assignments in time
     *
     * @return Array
     */
    public function getCreatedOrUpdatedAssignmentsResources(\DateTime $modifiedStart, \DateTime $modifiedEnd, \DateTime $start, \DateTime $end)
    {
        $qb = $this->createQueryBuilder('resource')

          ->innerJoin('resource.assignments', 'assignments')
          ->innerJoin('resource.user', 'user')

          ->addSelect('user')

          ->orwhere('assignments.updated >= :modifiedStart AND assignments.updated <= :modifiedEnd')
          ->orWhere('assignments.created >= :modifiedStart AND assignments.created <= :modifiedEnd')

          ->andWhere('assignments.day <= :end')
          ->andWhere('assignments.day >= :start')

          ->setParameter('modifiedStart', $modifiedStart)
          ->setParameter('modifiedEnd', $modifiedEnd)
          ->setParameter('start', $start)
          ->setParameter('end', $end);

        return $qb->getQuery()->getResult();
    }
}
