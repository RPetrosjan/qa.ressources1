<?php

namespace Application\Sonata\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    /**
     * Loads users that are not yet linked to a resource.
     *
     * @param User $user a given user, to add its resource (eg: edit user form)
     *
     * @return QueryBuilder
     */
    public function getUnlinkedUsers(User $user = null)
    {
        $qb = $this->createQueryBuilder('u')
                   ->where('NOT EXISTS (SELECT r FROM Act\ResourceBundle\Entity\Resource r WHERE r.user = u.id)');

        if ($user != null) {
            // Also select the given user resource
            $qb->orWhere('u.id = :user_id')
               ->setParameter('user_id', $user->getId());
        }

        return $qb;
    }

    /**
     * Returns users that have subscribe to at least one team
     * for the previsional email
     *
     * @return array
     */
    public function getPrevisionalSubscribers()
    {
        $qb = $this->createQueryBuilder('user')
          ->innerJoin('user.previsionalTeams', 'team')
          ->addSelect('team');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get users that has username like the given string.
     *
     * @param $string
     *
     * @return array
     */
    public function getUsersLike($string)
    {
        $qb = $this->createQueryBuilder('user')
            ->where('user.username LIKE :string')
            ->setParameter('string', '%' . $string . '%');

        return $qb->getQuery()->getResult();
    }
}
