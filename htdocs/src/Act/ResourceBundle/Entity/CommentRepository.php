<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * CommentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CommentRepository extends EntityRepository
{
    public function getUserComments(\Application\Sonata\UserBundle\Entity\User $user)
    {
        $qb = $this->createQueryBuilder('comments')
                ->leftJoin('comments.project', 'project')
                ->addSelect('project')

                ->andWhere('comments.user = :user')
                ->setParameter(':user', $user);

        return $qb->getQuery()->getResult();
    }
}
