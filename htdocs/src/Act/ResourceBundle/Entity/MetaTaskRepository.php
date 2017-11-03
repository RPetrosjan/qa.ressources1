<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Contient toutes les méthodes pour récupèrer une collection
 * de métatâches dans la base de données.
 *
 */
class MetaTaskRepository extends EntityRepository
{
    /**
     * Récupère les métatâches du projet
     */
    public function getMetaTasksForProject(Project $p)
    {
        $qb = $this->createQueryBuilder('ta')
            ->leftJoin('ta.commontasks', 'commons')
            ->leftJoin('commons.teams', 'cteams')
            ->leftJoin('commons.teamprofiles', 'ctprof')
            ->leftJoin('commons.subtasks', 'subtasks')
            ->leftJoin('subtasks.teams', 'steams')
            ->leftJoin('subtasks.teamprofiles', 'stprof')
            ->leftJoin('ta.teams', 'teams')
            ->leftJoin('ta.teamprofiles', 'tprof')

            ->addSelect('commons')
            ->addSelect('cteams')
            ->addSelect('ctprof')
            ->addSelect('subtasks')
            ->addSelect('steams')
            ->addSelect('stprof')
            ->addSelect('teams')
            ->addSelect('tprof')

            ->where('ta.project = :project')
            ->setParameter('project', $p);

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Récupère les métatâches du projet
     */
    public function getMetaTasksForProjectArray(Array $p)
    {
        $qb = $this->createQueryBuilder('ta')
            ->leftJoin('ta.commontasks', 'commons')
            ->leftJoin('commons.teams', 'cteams')
            ->leftJoin('commons.teamprofiles', 'ctprof')
            ->leftJoin('commons.subtasks', 'subtasks')
            ->leftJoin('subtasks.teams', 'steams')
            ->leftJoin('subtasks.teamprofiles', 'stprof')
            ->leftJoin('ta.teams', 'teams')
            ->leftJoin('ta.teamprofiles', 'tprof')

            ->addSelect('commons')
            ->addSelect('cteams')
            ->addSelect('ctprof')
            ->addSelect('subtasks')
            ->addSelect('steams')
            ->addSelect('stprof')
            ->addSelect('teams')
            ->addSelect('tprof')

            ->where('ta.project = :project')
            ->setParameter('project', $p['id']);

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Récupère les métatâches du projet avec commentaires
     */
    public function getMetaTasksForProjectWithComments(Project $p)
    {
        $qb = $this->createQueryBuilder('ta')
            ->leftJoin('ta.commontasks', 'commons')
            ->leftJoin('commons.teams', 'cteams')
            ->leftJoin('commons.teamprofiles', 'ctprof')
            ->leftJoin('commons.comments', 'ccoms')
            ->leftJoin('commons.assignments', 'cass')
            ->leftJoin('commons.subtasks', 'subtasks')
            ->leftJoin('subtasks.teams', 'steams')
            ->leftJoin('subtasks.teamprofiles', 'stprof')
            ->leftJoin('subtasks.comments', 'scoms')
            ->leftJoin('subtasks.assignments', 'sass')
            ->leftJoin('ta.teams', 'teams')
            ->leftJoin('ta.teamprofiles', 'tprof')
            ->leftJoin('ta.comments', 'tcoms')

            ->addSelect('commons')
            ->addSelect('cteams')
            ->addSelect('ctprof')
            ->addSelect('ccoms')
            ->addSelect('cass')
            ->addSelect('subtasks')
            ->addSelect('steams')
            ->addSelect('stprof')
            ->addSelect('scoms')
            ->addSelect('sass')
            ->addSelect('teams')
            ->addSelect('tprof')
            ->addSelect('tcoms')

            ->where('ta.project = :project')
            ->setParameter('project', $p)
            ->addOrderBy('ta.start')
            ->addOrderBy('ta.end');

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * Récupère les métatâches du projet
     */
    public function getMetaTasksForProjectLaterThanDate(Project $p, \DateTime $d)
    {
        $qb = $this->createQueryBuilder('ta')
            ->leftJoin('ta.commontasks', 'ctask')
            ->leftJoin('ctask.teams', 'cteams')
            ->leftJoin('ctask.teamprofiles', 'ctprof')
            ->leftJoin('ctask.subtasks', 'subtasks')
            ->leftJoin('subtasks.teams', 'steams')
            ->leftJoin('subtasks.teamprofiles', 'stprof')
            ->leftJoin('ta.teams', 'teams')
            ->leftJoin('ta.teamprofiles', 'tprof')

            ->addSelect('ctask')
            ->addSelect('cteams')
            ->addSelect('ctprof')
            ->addSelect('subtasks')
            ->addSelect('steams')
            ->addSelect('stprof')
            ->addSelect('teams')
            ->addSelect('tprof')

            ->where('ta.project = :project')
            ->andWhere('ta.end >= :date')

            ->setParameter('project', $p)
            ->setParameter('date', $d);

        return $qb->getQuery()->getResult();
    }

    public function getMetaTasksForProjectArrayLaterThanDate(Array $p, \DateTime $d)
    {
        $qb = $this->createQueryBuilder('ta')
            ->leftJoin('ta.commontasks', 'ctask')
            ->leftJoin('ctask.teams', 'cteams')
            ->leftJoin('ctask.teamprofiles', 'ctprof')
            ->leftJoin('ctask.subtasks', 'subtasks')
            ->leftJoin('subtasks.teams', 'steams')
            ->leftJoin('subtasks.teamprofiles', 'stprof')
            ->leftJoin('ta.teams', 'teams')
            ->leftJoin('ta.teamprofiles', 'tprof')

            ->addSelect('ctask')
            ->addSelect('cteams')
            ->addSelect('ctprof')
            ->addSelect('subtasks')
            ->addSelect('steams')
            ->addSelect('stprof')
            ->addSelect('teams')
            ->addSelect('tprof')

            ->where('ta.project = :project')
            ->andWhere('ta.end >= :date')

            ->setParameter('project', $p['id'])
            ->setParameter('date', $d);

        return $qb->getQuery()->getResult();
    }

}
