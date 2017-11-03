<?php

namespace Application\Sonata\UserBundle\Tests\Entity\Repository;

use Act\MainBundle\Tests\CustomTestCase;
use Application\Sonata\UserBundle\Entity\User;
use Act\ResourceBundle\Entity\Resource;

/**
 * Testing the User Repository
 */
class UserRepositoryTest extends CustomTestCase
{
    /**
     * Testing getUnlinkedUsers()
     */
    public function testGetUnlinkedUsers()
    {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        $em->beginTransaction();

        /**
         * By default, should be no unlinked users
         */
        $queryBuilder = $em->getRepository('ApplicationSonataUserBundle:User')->getUnlinkedUsers();
        $users = $queryBuilder->getQuery()->getResult();
        $usernames = array();
        if (count($users) > 0) {
            foreach ($users as $user) {
                $usernames[] = $user->getUsername();
            }
        }
        $this->assertCount(0, $users, 'By default, should be no unlinked users - check fixtures or tests insulation - '.implode(', ', $usernames));

        // Create a new unlinked user
        $user = new User();
        $user->setUsername('test.test');
        $user->setEmail('test@test.tst');
        $em->persist($user);
        $em->flush();

        /**
         * Now there should be one user
         */
        $queryBuilder = $em->getRepository('ApplicationSonataUserBundle:User')->getUnlinkedUsers();
        $users = $queryBuilder->getQuery()->getResult();
        $this->assertCount(1, $users);

        // Link a resource to this user
        $resource = $em->getRepository('ActResourceBundle:Resource')->find(4);
        $resource->setUser($user);
        $em->flush();

        /**
         * No more unlinked users
         */
        $queryBuilder = $em->getRepository('ApplicationSonataUserBundle:User')->getUnlinkedUsers();
        $users = $queryBuilder->getQuery()->getResult();
        $this->assertCount(0, $users);

        /**
         * Use parameter, should be one user : the new one
         */
        $queryBuilder = $em->getRepository('ApplicationSonataUserBundle:User')->getUnlinkedUsers($user);
        $users = $queryBuilder->getQuery()->getResult();
        $this->assertCount(1, $users);

        $em->rollback();
    }

    /**
     * Testing getUsersLike()
     */
    public function testGetUsersLike()
    {
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();

        $em->beginTransaction();

        /**
         * Checks %ressources.% users
         */
        $users = $em->getRepository('ApplicationSonataUserBundle:User')->getUsersLike('ressources.');
        $this->assertCount(3, $users, 'By default, should be 3 users like %ressource.%- check fixtures or tests insulation');

        /**
         * Checks %ourc% users
         */
        $users = $em->getRepository('ApplicationSonataUserBundle:User')->getUsersLike('ourc');
        $this->assertCount(3, $users);

        /**
         * Checks %salut% users
         */
        $users = $em->getRepository('ApplicationSonataUserBundle:User')->getUsersLike('salut');
        $this->assertCount(0, $users);

        // Create a new unlinked user
        $user = new User();
        $user->setUsername('salut.cava');
        $user->setEmail('salut@test.tst');
        $em->persist($user);
        $em->flush();

        /**
         * Checks %salut% users
         */
        $users = $em->getRepository('ApplicationSonataUserBundle:User')->getUsersLike('alu');
        $this->assertCount(1, $users);

        $em->rollback();
    }
}
