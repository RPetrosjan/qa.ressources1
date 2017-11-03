<?php

namespace Application\Sonata\UserBundle\Tests\Entity;

use Application\Sonata\UserBundle\Entity\User;
use Act\ResourceBundle\Entity\Team;
use Act\ResourceBundle\Entity\Resource;

/**
 * Testing the User Entity
 */
class UserEntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing compare()
     */
    public function testCompare()
    {
        $user = new User();
        $user->setUsername('test.user');

        $resource = new Resource();
        $resource->setNameShort('TER');

        // Should be compared with result true
        $this->assertTrue($user->compare($resource));

        // Should still be compared with result true
        $user->setUsername('test.use');
        $this->assertTrue($user->compare($resource));

        // Should be compared with false
        $resource->setNameShort('ABI');
        $this->assertFalse($user->compare($resource));

        // Try with a shorter username
        $user->setUsername('ab');
        $this->assertTrue($user->compare($resource));

        $user->setUsername('tr');
        $this->assertFalse($user->compare($resource));
    }

    /**
     * Testing hasSubscribedTo()
     */
    public function testHasSubscribedTo()
    {
        $user = new User();
        $user->setUsername('test.user');

        $team = new Team();
        $team->setName('Test team');

        // No subscriptions here
        $this->assertFalse($user->hasSubscribedTo($team));

        // Add the subscription
        $user->addPrevisionalTeam($team);
        $this->assertTrue($user->hasSubscribedTo($team));
    }

    /**
     * Testing resetPrevisionalTeams()
     */
    public function testResetPrevisionalTeams()
    {
        $team = new Team();
        $team->setName('Test team');

        $user = new User();
        $user->setUsername('test.user');
        $user->addPrevisionalTeam($team);

        // Check teams
        $this->assertTrue(count($user->getPrevisionalTeams()) == 1);

        // Reset previsional teams
        $user->resetPrevisionalTeams();
        $this->assertTrue(count($user->getPrevisionalTeams()) == 0);
    }
}
