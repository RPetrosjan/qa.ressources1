<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Assignment Entity Repository
 *
 * Contains all queries to gather a collection of
 * assignments from the database.
 */
class AssignmentRepository extends EntityRepository
{
    /**
     * Récupère les affectations pour une ressource donnée
     * @param  Resource $resource
     * @return Array
     */
    public function getAllResourceAssignments(Resource $resource)
    {
        $qb = $this->createQueryBuilder('a')
                ->addSelect('a')
                ->where('resource = :resource')
                ->setParameter('resource', $resource);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les affectations pour un projet donné, entre deux dates données
     * @param  Project  $resource
     * @param  DateTime $start
     * @param  DateTime $end
     * @return Array
     */
    public function getAssignmentsForProject(Project $project, \DateTime $start, \DateTime $end)
    {
        $qb = $this->createQueryBuilder('a')

          ->join('a.project', 'p')
          ->leftJoin('a.commontask', 't')
          ->join('a.resource', 'r')

          ->addSelect('t')
          ->addSelect('p')

          ->where('p.id = :id')
          ->andWhere('a.day >= :start')
          ->andWhere('a.day <= :end')

          ->setParameter('id', $project)
          ->setParameter('start', $start)
          ->setParameter('end', $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les affectations pour une ressource donnée, entre deux dates données
     * @param  int      $resource
     * @param  DateTime $start
     * @param  DateTime $end
     * @return Array
     */
    public function getAssignmentsForResource($resource, \DateTime $start, \DateTime $end)
    {
        $qb = $this->createQueryBuilder('a')

                ->join('a.project', 'p')
                ->leftJoin('a.commontask', 't')
                ->join('a.resource', 'r')

                ->addSelect('t')
                ->addSelect('p')

                ->where('r.id = :id')
                ->andWhere('a.day >= :start')
                ->andWhere('a.day <= :end')
                ->andWhere('p.active = 1')

                ->setParameter('id', $resource)
                ->setParameter('start', $start)
                ->setParameter('end', $end)

                ->addOrderBy('p.name');

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the sum of the assigned workload for a given date period and resource
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @param Resource  $resource
     *
     * @return float
     */
    public function getAssignmentsSumForResource(\DateTime $start, \DateTime $end, Resource $resource)
    {
        $qb = $this->createQueryBuilder('assignment')
          ->select('SUM(assignment.workload_assigned) AS total')
          ->join('assignment.project', 'project')
          ->where('assignment.resource = :resource')
          ->andWhere('assignment.day >= :start')
          ->andWhere('assignment.day <= :end')
          ->andWhere('project.active = 1')
          ->setParameter('resource', $resource)
          ->setParameter('start', $start)
          ->setParameter('end', $end);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the sum of workload assignment to holidays projects
     * for the given period and resource.
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @param Resource  $resource
     *
     * @return float
     */
    public function getHolidays(\DateTime $start, \DateTime $end, Resource $resource)
    {
        $qb = $this->createQueryBuilder('assignment')
          ->select('SUM(assignment.workload_assigned) AS total')
          ->join('assignment.project', 'project')
          ->where('assignment.resource = :resource')
          ->andWhere('assignment.day >= :start')
          ->andWhere('assignment.day <= :end')
          ->andWhere('project.active = 1')
          ->andWhere('project.typeHoliday = 1')
          ->setParameter('resource', $resource)
          ->setParameter('start', $start)
          ->setParameter('end', $end);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Returns the sum of the assigned workload for a given date period, resource, one or more selected projects and selected tags
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @param Resource  $resource
     * @param array $projects
     * @param array $tags
     * @param array $tagsExclude
     *
     * @return float
     */
    public function getAssignmentsSumForResourceByProjectAndTags(\DateTime $start, \DateTime $end, Resource $resource, array $projects = null, array $tags = null, array $tagsExclude = null)
    {
        // Select assignments from projects WITH these tags.
        $lineTags = '';
        for ($i = 0; $i < count($tags); $i++) {
            if ($tags[$i] == "typeInactive") {
                $lineTags .= 'project.active = 0';
            } else {
                $lineTags .= 'project.' . $tags[$i] . ' = 1';
            }

            if ($i < count($tags) - 1) {
                $lineTags .= ' or ';
            }
        }

        // Select assignments from projects WITHOUT these tags.
        $lineTagsExclude = '';
        for ($i = 0; $i < count($tagsExclude); $i++) {
            if ($tagsExclude[$i] == "typeInactive") {
                $lineTagsExclude .= 'project.active = 1';
            } else {
                $lineTagsExclude .= 'project.' . $tagsExclude[$i] . ' = 0';
            }

            if ($i < count($tagsExclude) - 1) {
                $lineTagsExclude .= ' or ';
            }
        }

        // Select assignments from these projects only.
        $lineProjects = '';
        for ($i = 0; $i < count($projects); $i++) {
            $lineProjects .= 'project.id =' . $projects[$i];
            if ($i < count($projects) - 1) {
                $lineProjects .= ' or ';
            }
        }

        $qb = $this->createQueryBuilder('assignment')
          ->select('SUM(assignment.workload_assigned) AS total')
          ->join('assignment.project', 'project')
          ->where('assignment.resource = :resource')
          ->andWhere('assignment.day >= :start')
          ->andWhere('assignment.day <= :end');

        if (strlen($lineProjects) > 0) $qb->andWhere($lineProjects);
        if (strlen($lineTags) > 0) $qb->andWhere($lineTags);
        if (strlen($lineTagsExclude) > 0) $qb->andWhere($lineTagsExclude);

        $qb
          ->setParameter('resource', $resource)
          ->setParameter('start', $start)
          ->setParameter('end', $end);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Récupère les affectations pour la ressource et le projet donné
     * @param  Resource $resource
     * @param  Project  $project
     * @return Array
     */
    public function getAssignmentsForThisResourceAndProject(Resource $resource, Project $project)
    {
        $qb = $this->createQueryBuilder('a')
                ->where('a.resource = :resource')
                ->andWhere('a.project = :project')

                ->setParameter('resource', $resource)
                ->setParameter('project', $project);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les affectations de ce projet, et ordonné selon le jour, dans le sens donné par timeDirection
     * @param  Project $project
     * @param  int     $timeDirection - 0 = passé (Order By day ASC) - 1 = futur (Order By day DESC)
     * @return Array
     */
    public function getAssignmentsForThisProjectOrderByDay(Project $project, $timeDirection)
    {
        $qb = $this->createQueryBuilder('a')
                ->where('a.project = :project')
                ->setParameter('project', $project)
                ->addOrderBy('a.day', ($timeDirection == 0 ? 'ASC' : 'DESC'));

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les affectations pour tous les autres projets, délimité dans le temps
     *
     * @param  Project  $project
     * @param  DateTime $start
     * @param  DateTime $end
     * @return Array
     */
    public function getAssignmentsNotThisProject(Project $project, \DateTime $start, \DateTime $end)
    {
        $qb = $this->createQueryBuilder('a')
                ->join('a.resource', 'r')
                ->join('a.project', 'p')

                ->addSelect('r')

                ->andWhere('a.project != :project AND p.active = 1')
                ->andWhere('a.day >= :start', 'a.day <= :end')

                ->setParameter('project', $project)
                ->setParameter('start', $start)
                ->setParameter('end', $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les affectations pour tous les autres projets, délimité dans le temps
     *
     * @param  array    $projects
     * @param  DateTime $start
     * @param  DateTime $end
     * @return Array
     */
    public function getAssignmentsNotTheseProjects(array $projects, \DateTime $start, \DateTime $end)
    {
        $pids = array();
        foreach ($projects as $p) {
            $pids[] = $p->getId();
        }

        $qb = $this->createQueryBuilder('a')
                ->join('a.resource', 'r')
                ->join('a.project', 'p')

                ->addSelect('r')

                ->andWhere('p.active = 1')
                ->andWhere('a.day >= :start', 'a.day <= :end')

                ->setParameter('start', $start)
                ->setParameter('end', $end);

        if (count($pids) > 0) {
            $qb->andWhere('p.id NOT IN('.implode(',',$pids).')');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les affectations pour tous les autres projets et pour cette ressource
     * délimité dans le temps avec des dates de début et de fin
     *
     * @param  Project  $project
     * @param  Resource $resource
     * @param  DateTime $start
     * @param  DateTime $end
     * @param  boolean  $inverse
     * @return Array
     */
    public function getResourceAssignmentsForProject(Project $project, Resource $resource, \DateTime $start, \DateTime $end, $inverse = false, $includeInactiveProjects = false)
    {
        $op = '=';
        if ($inverse) {
            $op = '!=';
        }

        $qb = $this->createQueryBuilder('a')
                ->join('a.resource', 'r')
                ->join('a.project', 'p')
                ->leftJoin('a.commontask', 'ct')
                ->leftJoin('a.subtask', 'st')

                ->andWhere('a.resource = :resource')
                ->andWhere('a.day >= :start', 'a.day <= :end')

                ->addOrderBy('a.day', 'ASC')

                ->addSelect('ct')
                ->addSelect('st')

                ->setParameter('resource', $resource)
                ->setParameter('start', $start)
                ->setParameter('end', $end);

        if ($includeInactiveProjects) {
            $qb
                ->andWhere('a.project '.$op.' :project')
                ->setParameter('project', $project);
        } else {
            $qb
                ->andWhere('a.project '.$op.' :project AND p.active = 1')
                ->setParameter('project', $project);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les prochaines affectations assignées à la ressource
     * Utilisé dans le tableau de bord
     * @param  Resource $r
     * @return Array
     */
    public function getIncommingAssignments(Resource $r)
    {
        $qb = $this->createQueryBuilder('assignments')
                ->join('assignments.project', 'project')
                ->addSelect('project')

                ->andWhere('assignments.resource = :resource')
                ->andWhere('assignments.day >= :today')
                ->andWhere('project.active = 1')

                ->setParameter('resource', $r)
                ->setParameter('today', new \DateTime());

        return $qb->getQuery()->getResult();
    }

    public function getAssignmentForTaggedProject($tag, $start, $end)
    {

        $qb = $this->createQueryBuilder('assignments')
                ->join('assignments.project', 'project')
                ->addSelect('project')
                ->where('assignments.day >= :start', 'assignments.day <= :end')
                ->andWhere('project.'. $tag .' = 1')

                ->setParameter('start', $start)
                ->setParameter('end', $end);

        return $qb->getQuery()->getResult();
    }

}
