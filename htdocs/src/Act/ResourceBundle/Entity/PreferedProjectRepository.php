<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Application\Sonata\UserBundle\Entity\User;

/**
 * Contient toutes les méthodes pour récupèrer une collection
 * de projets préférés dans la base de données.
 *
 */
class PreferedProjectRepository extends EntityRepository
{
    /**
     * Récupère les projets préférés pour un utilisateur donné
     * @param User $u
     */
    public function getPreferedProjectsOfUser(User $u)
    {
        $qb = $this->createQueryBuilder('pp')
                ->where('pp.user = :user')
                ->setParameter('user', $u);

        return $qb->getQuery()->getResult();
    }
    /**
     * Récupère les projets préférés pour un utilisateur donné, ordonnées, et avec leur projet
     * @param User $u
     */
    public function getPreferedProjectsOrdered(User $u)
    {
        $qb = $this->createQueryBuilder('pp')
                ->innerJoin('pp.project', 'proj')
                ->where('pp.user = :user')
                ->addSelect('proj')
                ->orderBy('proj.name')
                ->setParameter('user', $u);

        return $qb->getQuery()->getResult();
    }
}
