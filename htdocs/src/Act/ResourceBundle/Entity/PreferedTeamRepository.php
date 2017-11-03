<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Contient toutes les méthodes pour récupèrer une collection
 * d'équipes préférées dans la base de données.
 *
 */
class PreferedTeamRepository extends EntityRepository
{
    /**
     * Récupère les équipes préférées pour un projet donné
     * @param Project $p
     */
    public function getPreferedTeamsOfProject(Project $p)
    {
        $qb = $this->createQueryBuilder('pt')
                ->where('pt.project = :project')
                ->setParameter('project', $p);

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les équipes préférées ce repportant à la team donné
     * @param Team $t
     */
    public function getPreferedTeams(Team $t)
    {
        $qb = $this->createQueryBuilder('pt')
                ->where('pt.team = :team')
                ->setParameter('team', $t);

        return $qb->getQuery()->getResult();
    }
}
