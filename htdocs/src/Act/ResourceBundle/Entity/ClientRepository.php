<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Client Entity Repository
 *
 * Contains all queries to gather a collection of
 * clients from the database.
 */
class ClientRepository extends EntityRepository
{
    /**
     * Get all clients ordered by name
     * @codeCoverageIgnore
     *
     * @return QueryBuilder
     */
    public function getClientOrderByName()
    {
        $qb = $this->createQueryBuilder('c')
                ->addSelect('c')
                ->addOrderBy('c.name');
        return $qb;
    }
}
