<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;

/**
 * Contient toutes les méthodes pour récupèrer une collection
 * d'équipes dans la base de données.
 *
 */
class TeamRepository extends EntityRepository
{

    public function getTeamWithId($id)
    {
        $qb = $this->createQueryBuilder('t')
        ->leftJoin('t.manager', 'manager')
        ->addSelect('t')
        ->addSelect('manager')
        ->where('t.id = :id')->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère la liste des équipes avec leur manager
     */
    public function getTeamsWithManager()
    {
        $qb = $this->createQueryBuilder('a')
                ->leftJoin('a.manager', 'c')
                ->addSelect('c')
                ->addOrderBy('a.name');

        return $qb->getQuery()->getResult();
    }

    /**
     * Renvoi la liste des équipes avec au moins une ressource
     */
    public function getNotEmptyTeamsWithManager()
    {
        $qb = $this->createQueryBuilder('a')
                ->leftJoin('a.manager', 'c')
                ->join('a.resources', 'r')
                ->addSelect('c')
                ->addOrderBy('a.name', 'asc');

        return $qb->getQuery()->getResult();
    }

    /**
     * Renvoi la liste des équipes avec au moins une ressource et recupère ses ressources
     */
    public function getNotEmptyTeamsWithManagerResources()
    {
        $qb = $this->createQueryBuilder('a')
                ->leftJoin('a.manager', 'c')
                ->join('a.resources', 'r')
                ->addSelect('r')
                ->addSelect('c')
                ->addOrderBy('a.name', 'asc');

        return $qb->getQuery()
                ->getResult();
    }

    /**
     * Récupère la liste des équipes et ressources avec :
     *  - les ressources
     *      - le lieu de la ressource
     *  - le manager
     *
     *  Trié par nom d'équipe, puis nom de ressource
     *
     *  Utilisé dans la génération du planning projet
     *  @param DateTime $start
     *  @param DateTime $end
     *  @param Array $team - la liste des ID des équipes à charger
     */
    public function getTeamsManagerResourcesLocation(\DateTime $start = null, \DateTime $end = null, array $teams = null)
    {
        $qb = $this->createQueryBuilder('team')
                ->leftJoin('team.manager', 'manager')
                ->addSelect('manager')
                ->leftJoin('team.resources', 'resource')
                ->addSelect('resource')
                ->join('resource.location', 'loc')
                ->addSelect('loc')
                ->addOrderBy('team.name', 'asc')
                ->addOrderBy('resource.nameShort', 'asc');

        $this->addWhereResourceDates($qb, $start, $end);
        $this->addWhereTeamIn($qb, $teams);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère la liste des équipes et ressources qui travaillent sur le projet avec :
     *  - les ressources qui travaillent sur le projet
     *      - le lieu de la ressource
     *      - les affectations de la ressource
     *  - le manager
     *
     *  Trié par nom d'équipe, puis nom de ressource
     *  Utilisé dans la génération du planning projet
     *  @param Project $project
     *  @param DateTime $start
     *  @param DateTime $end
     *  @param Array $team - la liste des ID des équipes à charger
     */
    public function getTeamsResourcesAssignmentsForProject(Project $project, \DateTime $start = null, \DateTime $end = null, array $teams = null)
    {
        $qb = $this->createQueryBuilder('team')
                ->leftJoin('team.manager', 'manager')
                ->addSelect('manager')
                ->leftJoin('team.resources', 'resource')
                ->addSelect('resource')
                ->join('resource.location', 'loc')
                ->addSelect('loc')
                ->join('resource.assignments', 'assignment')
                ->join('assignment.project', 'proj')
                ->where('proj = :project')
                ->setParameter('project', $project)
                ->addOrderBy('team.name', 'asc')
                ->addOrderBy('resource.nameShort', 'asc');

        $this->addWhereResourceDates($qb, $start, $end);
        $this->addWhereTeamIn($qb, $teams);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère toutes les équipes avec leurs ressources et leurs affectations
     * on ne prend que les affectations qui correspondent à la période
     * NB: Utilisé pour le récapitulatif Annuel
     *
     * @param  DateTime $start
     * @param  DateTime $end
     * @return Array
     */
    public function getTeamsResourcesAssignments(\DateTime $start = null, \DateTime $end = null)
    {
        $qb = $this->createQueryBuilder('team');
                if ($start != null && $end != null) {
                    $qb
                    ->leftJoin('team.resources', 'ress', Expr\Join::WITH, 'ress.start <= :end AND (ress.end is NULL OR ress.end >= :start)')
                        ->leftJoin('ress.location', 'loc')
                    ->leftJoin('ress.assignments', 'assignment', Expr\Join::WITH, 'assignment.day >= :start AND assignment.day <= :end')
                    ->setParameter('start', $start)
                    ->setParameter('end', $end);
                } else {
                    $qb
                    ->leftJoin('team.resources', 'ress')
                        ->leftJoin('ress.location', 'loc')
                    ->leftJoin('ress.assignments', 'assignment');
                }

                $qb
                ->addSelect('ress')
                ->addSelect('assignment')
                ->addSelect('loc')

                ->addOrderBy('team.name', 'asc')
                ->addOrderBy('ress.nameShort', 'asc');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère toutes les équipes avec leurs ressources, peut utiliser une période
     * pour réduire les résultats
     */
    public function getTeamsWithResources(\DateTime $start = null, \DateTime $end = null)
    {
        $qb = $this->createQueryBuilder('team')
                ->leftJoin('team.resources', 'resource')
                ->leftJoin('team.profiles', 'prof')
                ->addSelect('resource')
                ->addSelect('prof')
                ->addOrderBy('team.name', 'asc')
                ->addOrderBy('resource.name', 'asc');

        $this->addWhereResourceDates($qb, $start, $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère toutes les équipes avec leurs profils
     */
    public function getTeamsWithProfiles()
    {
        $qb = $this->createQueryBuilder('team')
                ->leftJoin('team.profiles', 'prof')
                ->addSelect('prof')
                ->addOrderBy('team.name', 'asc');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the data needed to display the year summary of resource use
     * @param  \DateTime $start
     * @param  \DateTime $end
     * @return Array
     */
    public function getYearSummaryData(\DateTime $start, \DateTime $end)
    {
        $qb = $this->createQueryBuilder('team')
                ->join('team.resources', 'resource', Expr\Join::WITH, 'resource.start <= :end AND (resource.end is NULL OR resource.end >= :start)')
                ->leftJoin('resource.assignments', 'assignment', Expr\Join::WITH, 'assignment.day >= :start AND assignment.day <= :end')
                ->leftJoin('assignment.project', 'project')

                ->addSelect('resource')
                ->addSelect('assignment')
                ->addSelect('project')

                ->addOrderBy('team.name', 'asc')
                ->addOrderBy('resource.nameShort', 'asc')

                ->setParameter('start', $start)
                ->setParameter('end', $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get a team given a manager (resource)
     * @param  Resource $manager
     * @return Team     or null
     */
    public function getTeamWithManagerObject(Resource $manager)
    {
        $qb = $this->createQueryBuilder('team')
            ->select('team')
            ->where("team.manager = :id")
            ->setParameter('id', $manager);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all teams that have susbscribers to the
     * previsional summary sent by mail
     *
     * @return array
     */
    public function getTeamsPrevisionalDistinct()
    {
        $qb = $this->createQueryBuilder('team')
          ->select('team')
          ->innerJoin('team.previsionalSubscribers', 'subscribers') // Only take teams that have previsional subscribers
          ->distinct();

        return $qb->getQuery()->getResult();
    }

    /**
     * Helper function to add a where team IN clause
     * @param $qb
     * @param $teams
     */
    private function addWhereTeamIn(&$qb, $teams)
    {
        if ($teams != null) {
            $qb->andWhere('team.id IN('.implode(',',$teams).')');
        }
    }

    /**
     * Helper function to add a where resources start/end clause
     * @param $qb
     * @param $start
     * @param $end
     */
    private function addWhereResourceDates(&$qb, $start, $end)
    {
        if ($start != null && $end != null) {
            $qb->andWhere('resource.start < :end', 'resource.end is NULL OR resource.end > :start')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        } elseif ($start != null) {
            $qb->andWhere('resource.start < :end')->setParameter('end', $end);
        } elseif ($end != null) {
            $qb->andWhere('resource.end is NULL OR resource.end > :start')->setParameter('start', $start);
        }
    }

    /**
     * Get all teams that have no CPT defined for this project
     *
     * @param Project $project
     * @param Team $team include this team (if it's the current one added to the project as a cpt)
     *
     * @return QueryBuilder
     */
    public function getTeamsWithoutCPTForProject(Project $project, Team $team = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb2 = $this->_em->createQueryBuilder();

        // Subquery
        $qb2->add('select', 'cpt')
          ->add('from', 'ActResourceBundle:ProjectCpt cpt')
          ->add('where',
            $qb2->expr()->andx(
              $qb2->expr()->eq('cpt.team', 't'),
              $qb2->expr()->eq('cpt.project', ':project')
            )
          )
        ;

        // Main query
        $qb->add('select', 't')->add('from', 'ActResourceBundle:Team t');

        if ($team != null) {
            // Add the given team to the SQL query
            $qb->add('where', $qb->expr()->orX(
                $qb->expr()->not(
                    $qb->expr()->exists(
                        $qb2
                    )
                ),
                't.id = :team_id'
            ))
            ->setParameter(':team_id', $team->getId());
        } else {
            // Only keep teams that have no CPT for this project
            $qb->add('where', $qb->expr()->not(
                    $qb->expr()->exists(
                        $qb2
                    )
                )
            );
        }

        $qb->setParameter(':project', $project);

        return $qb;
    }
}
