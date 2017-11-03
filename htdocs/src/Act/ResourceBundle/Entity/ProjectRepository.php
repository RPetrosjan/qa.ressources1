<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\EntityRepository;

/**
 * Contient toutes les méthodes pour récupèrer une collection
 * de projets dans la base de données.
 *
 */
class ProjectRepository extends EntityRepository
{
    /**
     * Récupère un projet
     * @param  int     $id id du projet
     * @return Project
     */
    public function getProject($id)
    {
        $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.cpf', 'cpf')
                ->leftJoin('p.cpts', 'cpts')
                ->addSelect('cpf')
                ->addSelect('cpts')
                ->where('p.id = :id')
                ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère un projet avec ses tâches
     * @param  int     $id id du projet
     * @return Project
     */
    public function getProjectWithTasks($id)
    {
        $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.tasks', 'ta')
                ->leftJoin('ta.teams', 'teams')
                ->leftJoin('ta.teamprofiles', 'teamprofs')

                ->addSelect('ta')
                ->addSelect('teams')
                ->addSelect('teamprofs')

                ->where('p.id = :id')

                ->addOrderBy('ta.start')

                ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Recupère tous les projects avec leurs tâches
     */
    public function getProjectsWithTasks()
    {
        $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.tasks', 'ta')
                ->leftJoin('ta.teams', 'teams')
                ->leftJoin('ta.teamprofiles', 'teamprofs')
                ->innerJoin('p.assignments','a')
                ->addSelect('ta')
                ->addSelect('teams')
                ->addSelect('teamprofs')
                ->addSelect('a')
                ->addOrderBy('ta.start');

        return $qb->getQuery()->getResult();
    }

    /**
     * Recupère tous les projects en veille : activés mais pas de tâche ni d'affectations
     * Tous les projets qui ne sont pas désactivés mais qui n'ont ni affectation ni tache sur la période demandée
     */
    public function getIdleProjects(\DateTime $start, \DateTime $end)
    {
        // Only get projects with no assignments and no tasks during the time period
        $query = $this->getEntityManager()->createQuery('
            SELECT p.id, p.name, p.color
            FROM ActResourceBundle:Project p
            WHERE NOT EXISTS (
                SELECT a.id
                FROM ActResourceBundle:Assignment a
                WHERE a.project = p.id
                AND a.day >= :start
                AND a.day <= :end
            )
            AND NOT EXISTS (
                SELECT t.id
                FROM ActResourceBundle:Task t
                WHERE t.project = p.id
                AND t.start >= :start
                AND t.end <= :end
            )
            AND p.active = 1
            ORDER BY p.name'
        );

        $query->setParameter(':start', $start);
        $query->setParameter(':end', $end);

        return $query->getResult();
    }

    /**
     * Projets sur lesquels il y a eu une affectation ou une tâche
     * dans la période demandée (toutes équipes confondu)
     */
    public function getProjectsWithAtLeastOneAssignmentTask(\DateTime $start, \DateTime $end, $status = 1)
    {
        // Only get projects with assignments or tasks during the time period
        $query = $this->getEntityManager()->createQuery('
            SELECT p.id, p.name, p.color, COUNT(DISTINCT ta.id) tasks, COUNT(DISTINCT a.id) assignments
            FROM ActResourceBundle:Project p
            LEFT JOIN p.tasks ta WITH ta.start >= :start AND ta.end <= :end
            LEFT JOIN p.assignments a WITH a.day >= :start AND a.day <= :end
            WHERE p.active = :status
            GROUP BY p.id
            HAVING tasks > 0 OR assignments > 0
            ORDER BY p.name'
        );

        $query->setParameter(':status', $status);
        $query->setParameter(':start', $start);
        $query->setParameter(':end', $end);

        return $query->getResult();
    }

    /**
     * Get projects with at least one assignment in the given period.
     */
    public function getProjectsWithAtLeastOneAssignment(\DateTime $start, \DateTime $end, $status = 1)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT p.id, p.name, p.color, p.start, p.end, COUNT(a.id) assignments, SUM(a.workload_assigned) total, COUNT(DISTINCT a.resource) resources
            FROM ActResourceBundle:Project p
            LEFT JOIN p.assignments a WITH a.day >= :start AND a.day <= :end
            WHERE p.active = :status
            GROUP BY p.id
            HAVING assignments > 0
            ORDER BY total DESC'
        );

        $query->setParameter('status', $status);
        $query->setParameter('start', $start);
        $query->setParameter('end', $end);

        // Get all projects with at least one assignment during given dates.
        $results = $query->getResult();

        // Do a second query to retrieve the details of the resources.
        if (is_array($results) && count($results) > 0) {
            foreach ($results as $i => $res) {
                $query = $this->getEntityManager()->createQuery('
                  SELECT r
                  FROM ActResourceBundle:Resource r
                  INNER JOIN r.assignments a WITH a.day >= :start AND a.day <= :end AND a.project = :id
                  ORDER BY r.team, r.name'
                );

                $query->setParameter('id', $res['id']);
                $query->setParameter('start', $start);
                $query->setParameter('end', $end);

                $results[$i]['resources'] = $query->getResult();
            }
        }

        return $results;
    }

    /**
     * Get projects with at least one assignment, for the given period, for given resources.
     *
     * @param \DateTime $start the starting date
     * @param \DateTime $end the ending date
     * @param array $resources a list of resources
     */
    public function getProjectsWithAtLeastOneResourceAssignment(\DateTime $start, \DateTime $end, array $ressources)
    {
        $query = $this->getEntityManager()->createQuery('
            SELECT p.id, p.name, p.color, p.start, p.end, COUNT(a.id) assignments, SUM(a.workload_assigned) total, COUNT(DISTINCT a.resource) resources
            FROM ActResourceBundle:Project p
            INNER JOIN p.assignments a WITH a.day >= :start AND a.day <= :end
            INNER JOIN a.resource r WITH r.id IN (:ids)
            WHERE p.active = 1
            GROUP BY p.id
            HAVING assignments > 0 AND resources > 0
            ORDER BY p.name'
        );

        $query->setParameter('ids', $ressources);
        $query->setParameter('start', $start);
        $query->setParameter('end', $end);

        return $query->getResult();
    }

    /**
     * Récupère le projet avec :
     *  - le chef de projet fonctionnel
     *  - les chefs de projets technique
     *  - les tâches
     *
     * NB: Utilisé pour générer le planning du projet
     *
     * @param  int     $id l'id du projet
     * @return Project
     */
    public function getProjectWithAllData($id)
    {
        $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.cpf', 'cpf')
                ->leftJoin('p.cpts', 'cpts')
                ->leftJoin('p.tasks', 'ta')
                 ->leftJoin('ta.teams', 'tateam')
                 ->leftJoin('ta.teamprofiles', 'tatp')

                ->addSelect('cpf')
                ->addSelect('cpts')
                ->addSelect('ta')
                 ->addSelect('tateam')
                 ->addSelect('tatp')

                ->where('p.id = :id')
                ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère le projet avec ses commentaires
     * @param int $id - id du projet
     */
    public function getProjectWithComments($id)
    {
        $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.comments', 'c')
                ->addSelect('c')
                ->where('p.id = :id')
                ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère le projet avec ses liens
     * @param int $id - id du projet
     */
    public function getProjectWithLinks($id)
    {
        $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.links', 'c')
                ->addSelect('c')
                ->where('p.id = :id')
                ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère un projet avec ses chefs de projet technique
     * @param int $id - id du projet
     */
    public function getProjectWithCpts($id)
    {
        $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.cpts', 'cpts')
                ->addSelect('cpts')
                ->where('p.id = :id')
                ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère les prochains projets actifs et ceux en cours
     */
    public function getIncommingProjects()
    {
        $today = new \DateTime();
        $today->setTime(0,0,0);

        $qb = $this->createQueryBuilder('project')
                ->leftJoin('project.assignments', 'assignments')
                ->leftJoin('project.tasks', 'tasks')
                ->where('assignments.day >= :today OR tasks.end >= :today')
                ->setParameter('today', $today)
                ->andWhere('project.active = 1')
                ->addOrderBy('project.name');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère tous les projets avec toutes les données
     * Utilisé pour lister tous les projets
     */
    public function getProjectsWithAllData()
    {
        $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.client', 'client')
                ->leftJoin('p.cpf', 'cpf')
                ->leftJoin('p.comments', 'comms')

                ->addSelect('client')
                ->addSelect('cpf')
                ->addSelect('comms');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère tous les projets actifs
     */
    public function getProjects()
    {
        $qb = $this->createQueryBuilder('p')
                ->where('p.active = 1')
                ->addOrderBy('p.name');

        return $qb->getQuery()->getResult();
    }

    /**
     * Recupère tous les projets inactifs
     */
    public function getInactiveProject()
    {
         $qb = $this->createQueryBuilder('p')
                ->where('p.active = 0')
                ->addOrderBy('p.name');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère tous les projets actifs de cette ressource
     */
    public function getResourceProjects(Resource $r, \DateTime $start = null, \DateTime $end = null)
    {
        $qb = $this->createQueryBuilder('p')
                ->join('p.assignments', 'ass')
                ->join('ass.resource', 'assress')
                ->where('p.active = 1')
                ->andWhere('assress = :resource')
                ->addOrderBy('p.name')
                ->setParameter('resource', $r);

        if ($start != null && $end != null) {
            $qb->andWhere('ass.day >= :start');
            $qb->andWhere('ass.day <= :end');
            $qb->setParameter('start', $start);
            $qb->setParameter('end', $end);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les projets dont le nom peut correspondre à celui donné
     * @param string $name
     */
    public function findProjectsByName($name)
    {
        $qb = $this->createQueryBuilder('project')
                ->andWhere('project.active = 1')
                ->andWhere('project.name LIKE :name')
                ->addOrderBy('project.name')
                ->setParameter('name', '%'.$name.'%');

        return $qb->getQuery()->getResult();
    }

    public function getProjectWithTasksFull($id)
    {
        $qb = $this->createQueryBuilder('p')
                ->leftJoin('p.tasks', 'ta')
                ->leftJoin('ta.teams', 'teams')
                ->leftJoin('ta.teamprofiles', 'teamprofs')

                ->addSelect('ta')
                ->addSelect('teams')
                ->addSelect('teamprofs')

                ->where('p.id = :id')

                ->addOrderBy('ta.start')

                ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère les projets d'une resource donnée dans les dates données
     *
     * @param  \Act\ResourceBundle\Entity\Resource $resource
     * @param  \DateTime                           $start
     * @param  \DateTime                           $end
     * @return Array
     */
    public function getProjectsOfResource(Resource $resource, \DateTime $start, \DateTime $end)
    {
        $qb = $this->createQueryBuilder('project')
                ->join('project.assignments', 'assignments')
                ->join('assignments.resource', 'resource')
                ->leftJoin('assignments.commontask', 'task')

                ->addSelect('assignments')
                ->addSelect('task')

                ->where('assignments.resource = :resource')
                ->andWhere('assignments.day >= :start')
                ->andWhere('assignments.day <= :end')
                ->andWhere('project.active = 1')

                ->setParameter('resource', $resource)
                ->setParameter('start', $start)
                ->setParameter('end', $end)

                ->addOrderBy('project.name');

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère, si il est existant, le projets dont la ressource est CPF
     *
     * @param \Act\ResourceBundle\Entity\Resource $resource
     */
    public function getProjectOfCpf(Resource $cpf)
    {
        $qb = $this->createQueryBuilder('project')
                    ->addSelect('project')
                    ->andWhere("project.cpf = :cpf")
                    ->setParameter("cpf", $cpf);

        return $qb->getQuery()->getResult();
    }

    /**
     *  Recupère tous les projets, trié par ordre alphabétique (nom)
     */
    public function findAllOrderByName()
    {
        $qb = $this->createQueryBuilder('p')
                ->addOrderBy('p.name');

        return $qb->getQuery()->getResult();
    }

    public function getHolidayProjects()
    {
        $qb = $this->createQueryBuilder('project')
            ->where('project.typeHoliday = 1')
            ->addOrderBy('project.name');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get active projects filtered by dates, types and resources.
     *
     * The filtering by resource means that every returned
     * project has at least one assignment for at least one
     * resource in the list.
     *
     * Used in the resource usage page.
     *
     * @param \DateTime $start the starting date
     * @param \DateTime $end the ending date
     * @param array $types a list of project types
     * @param array $resources a list of resources
     */
    public function getProjectsByDatesTypesResources(\DateTime $start, \DateTime $end, array $types = array(), array $resources = array())
    {
        // Gather projects with at least one assignments in period.
        if (count($resources) == 0) {
            // No resources specified.
            $projects = $this->getProjectsWithAtLeastOneAssignment($start, $end);
        } else {
            // Filter by resource.
            $projects = $this->getProjectsWithAtLeastOneResourceAssignment($start, $end, $resources);
        }

        // Gather ids.
        $ids = array();
        if (is_array($projects) && count($projects) > 0) {
            foreach ($projects as $project) {
                $ids[] = $project['id'];
            }
        }

        // Finally, filter projects by type.
        $qb = $this->createQueryBuilder('project')
          ->where('project.id IN (:ids)')
          ->addOrderBy('project.name')
          ->setParameter('ids', $ids);

        // Filter by types : AND (type1 OR type2...)
        // @TODO define in the entity or service a central place for these types.
            if (count($types) > 0) {
                $typeExp = array();
                foreach ($types as $type) {
                    if ($type != 'typeInactive') {
                        $typeExp[] = 'project.' . $type . ' = 1';
                    }
                }

                $qb->andWhere(implode(' OR ', $typeExp));
        }

        return $qb->getQuery()->getResult();
    }
}
