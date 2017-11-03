<?php

namespace Act\ResourceBundle\Tests\Services;

use Act\MainBundle\Tests\IsolatedTestCase;
use Act\ResourceBundle\Entity\Simulation;
use Act\ResourceBundle\Entity\Assignment;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Application\Sonata\UserBundle\Entity\User;

class SimulationTest extends IsolatedTestCase
{
    protected $simulationService;

    /**
     * Called before every tests
     */
    public function setUp()
    {
        parent::setUp();
        $this->simulationService = $this->client->getContainer()->get('act_resource.simulation');
    }

    /**
     * Test the rollback deletion of an assignment
     * 1. Add an assignment
     * 2. Throw a simulation
     * 3. Delete the assignment
     * 4. Rollback the simulation
     * 5. Check that the assignment is back and with same data
     */
    public function testDeleteRollback()
    {
        $day = new \DateTime('2014-06-20 00:00:00', new \DateTimeZone('Europe/Paris'));
        $workload = 2;
        $resource = $this->em->getRepository('ActResourceBundle:Resource')->find(1);
        $project = $this->em->getRepository('ActResourceBundle:Project')->find(2);
        $task = $this->em->getRepository('ActResourceBundle:Task')->find(2);
        $subtask = $this->em->getRepository('ActResourceBundle:Task')->find(4);

        /**
         * 1. Create an assignment
         */
        $a1 = new Assignment();
        $a1->setDay($day);
        $a1->setWorkload($workload);
        $a1->setResource($resource);
        $a1->setProject($project);
        $a1->setCommontask($task);
        $a1->setSubtask($subtask);
        $this->em->persist($a1);
        $this->em->flush();

        /**
         * 2. Throw a simulation
         */
        $user = $this->em->getRepository('ApplicationSonataUserBundle:User')->find(1);
        $this->createSimulation($user);

        /**
         * 3. Delete assignment
         */
        $this->em->remove($a1);
        $this->em->flush();

        // Check that the simulated assignment was created
        $sims = $this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findAll();
        $this->assertEquals(1, count($sims));

        // Check that the assignment is deleted
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
        ));
        $this->assertNull($assignment);

        /**
         * 4. Rollback the simulation
         */
        $this->simulationService->rollback();

        /**
         * 5. Check that the assignment was properly restored
         */
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
        ));
        $this->assertNotNull($assignment);

        // Check data
        $this->assertEquals($assignment->getWorkload(), $workload);
        $this->assertEquals($assignment->getCommontask(), $task);
        $this->assertEquals($assignment->getSubtask(), $subtask);

        // Check that the simulation is deleted
        $simulation = $this->em->getRepository('ActResourceBundle:Simulation')->findAll();
        $this->assertEquals(0, count($simulation));
        $sims = $this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findAll();
        $this->assertEquals(0, count($sims));

        // Reset the database to the initial state
        $this->em->remove($assignment);
        $this->em->flush();

        // Check that assignment is deleted
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
        ));
        $this->assertNull($assignment);
    }

    /**
     * Test the rollback update of an assignment
     * 1. Add an assignment
     * 2. Throw a simulation
     * 3. Update the assignment
     * 4. Update an other time the assignment
     * 5. Rollback the simulation
     * 6. Check that the assignment is back with same data
     */
    public function testUpdateRollback()
    {
        $day = new \DateTime('2014-06-20 00:00:00', new \DateTimeZone('Europe/Paris'));
        $workload = 2;
        $workload2 = 3;
        $workload3 = 4;
        $resource = $this->em->getRepository('ActResourceBundle:Resource')->find(1);
        $project = $this->em->getRepository('ActResourceBundle:Project')->find(2);
        $task = $this->em->getRepository('ActResourceBundle:Task')->find(2);
        $task2 = $this->em->getRepository('ActResourceBundle:Task')->find(3);
        $subtask = $this->em->getRepository('ActResourceBundle:Task')->find(4);
        $subtask2 = $this->em->getRepository('ActResourceBundle:Task')->find(5);
        $comment = 'Test';

        /**
         * 1. Create an assignment
         */
        $assignment = new Assignment();
        $assignment->setDay($day);
        $assignment->setWorkload($workload);
        $assignment->setResource($resource);
        $assignment->setProject($project);
        $assignment->setCommontask($task);
        $assignment->setSubtask($subtask);
        $this->em->persist($assignment);
        $this->em->flush();

        /**
         * 2. Throw a simulation
         */
        $user = $this->em->getRepository('ApplicationSonataUserBundle:User')->find(1);
        $this->createSimulation($user);

        /**
         * 3. Update assignment
         */
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
        ));
        $assignment->setCommontask($task2);
        $assignment->setSubtask($subtask2);
        $assignment->setWorkload($workload2);
        $assignment->setComment($comment);
        $this->em->persist($assignment);
        $this->em->flush();

        // Check that the simulated assignment was created
        $sims = $this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findAll();
        $this->assertEquals(1, count($sims));

        // Check that the assignment is modified
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
        ));
        $this->assertEquals($assignment->getCommontask(), $task2);
        $this->assertEquals($assignment->getSubtask(), $subtask2);
        $this->assertEquals($assignment->getWorkload(), $workload2);
        $this->assertEquals($assignment->getComment(), $comment);

        /**
         * 4. Update assignment an other time
         */
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
        ));
        $assignment->setWorkload($workload3);
        $this->em->persist($assignment);
        $this->em->flush();

        // Check that the simulated assignment was created
        $sims = $this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findAll();
        $this->assertEquals(2, count($sims));

        // Check that the assignment is modified
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
        ));
        $this->assertEquals($assignment->getCommontask(), $task2);
        $this->assertEquals($assignment->getSubtask(), $subtask2);
        $this->assertEquals($assignment->getWorkload(), $workload3);
        $this->assertEquals($assignment->getComment(), $comment);

        /**
         * 4. Rollback the simulation
         */
        $this->simulationService->rollback();

        /**
         * 5. Check that the assignment was properly restored
         */
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
        ));
        $this->assertEquals($assignment->getCommontask(), $task);
        $this->assertEquals($assignment->getSubtask(), $subtask);
        $this->assertEquals($assignment->getWorkload(), $workload);
        $this->assertNull($assignment->getComment());

        // Check that the simulation is deleted
        $simulation = $this->em->getRepository('ActResourceBundle:Simulation')->findAll();
        $this->assertEquals(0, count($simulation));
        $sims = $this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findAll();
        $this->assertEquals(0, count($sims));
    }

    /**
     * Test the rollback creation of an assignment
     * 1. Throw a simulation
     * 2. Add an assignment
     * 3. Rollback the simulation
     * 4. Check that the assignment is deleted
     */
    public function testCreationRollback()
    {
        $day = new \DateTime('2014-06-20 00:00:00', new \DateTimeZone('Europe/Paris'));
        $workload = 2;
        $resource = $this->em->getRepository('ActResourceBundle:Resource')->find(1);
        $project = $this->em->getRepository('ActResourceBundle:Project')->find(2);

        /**
         * 1. Throw a simulation
         */
        $user = $this->em->getRepository('ApplicationSonataUserBundle:User')->find(1);
        $this->createSimulation($user);

        /**
         * 2. Create an assignment
         */
        $a1 = new Assignment();
        $a1->setDay($day);
        $a1->setWorkload($workload);
        $a1->setResource($resource);
        $a1->setProject($project);
        $this->em->persist($a1);
        $this->em->flush();

        // Check that the simulated assignment was created
        $sims = $this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findAll();
        $this->assertEquals(1, count($sims));

        // Check that the assignment is created
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
        ));
        $this->assertNotNull($assignment);

        /**
         * 3. Rollback the simulation
         */
        $this->simulationService->rollback();

        /**
         * 4. Check that the assignment is deleted
         */
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
        ));
        $this->assertNull($assignment);

        // Check that the simulation is deleted
        $simulation = $this->em->getRepository('ActResourceBundle:Simulation')->findAll();
        $this->assertEquals(0, count($simulation));
        $sims = $this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findAll();
        $this->assertEquals(0, count($sims));
    }

    /**
     * Test the simulation commit feature
     * 1. Throw a simulation
     * 2. Create an assignment
     * 3. Commit the simulation
     * 4. Check the assignment is still here
     */
    public function testSimulationCommit()
    {
        $day = new \DateTime('2014-06-20 00:00:00', new \DateTimeZone('Europe/Paris'));
        $workload = 2;
        $resource = $this->em->getRepository('ActResourceBundle:Resource')->find(1);
        $project = $this->em->getRepository('ActResourceBundle:Project')->find(2);

        /**
         * 1. Throw a simulation
         */
        $user = $this->em->getRepository('ApplicationSonataUserBundle:User')->find(1);
        $this->createSimulation($user);

        /**
         * 2. Create an assignment
         */
        $a1 = new Assignment();
        $a1->setDay($day);
        $a1->setWorkload($workload);
        $a1->setResource($resource);
        $a1->setProject($project);
        $this->em->persist($a1);
        $this->em->flush();

        // Check simulated assignments
        $sims = $this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findAll();
        $this->assertEquals(1, count($sims));

        /**
         * 3. Commit the simulation
         */
        $this->simulationService->commit();

        // Simulation and simulated assignments must be deleted
        $this->assertEquals(0, count($this->em->getRepository('ActResourceBundle:Simulation')->findAll()));
        $this->assertEquals(0, count($this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findAll()));

        /**
         * 4. Check the assignment is still here
         */
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
        ));
        $this->assertNotNull($assignment);

        // Reset database to it's initial state
        $this->em->remove($assignment);
        $this->em->flush();

        // Check that assignment is deleted
        $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
            'project'   => $project,
            'resource'  => $resource,
            'day'       => $day
          ));
        $this->assertNull($assignment);
    }

    /**
     * Test commit when there is no active simulation
     * 1. Check that there is simulation
     * 2. Commit
     * 3. Catch exception
     */
    public function testCommitNoActiveSimulation()
    {
        /**
         * 1. Check that there is no simulation
         */
        $this->assertEquals(0, count($this->em->getRepository('ActResourceBundle:Simulation')->findAll()));

        try {
            /**
             * 2. Commit
             */
            $this->simulationService->commit();

            // If no exception - the test must fail
            $this->fail('An expected exception has not been raised.');
        } catch (\Exception $e) {
            /**
             * 3. Catch Exception
             */
            $this->assertEquals("No active simulation found", $e->getMessage());
        }
    }

    /**
     * Test rollback when there is no active simulation
     * 1. Check that there is simulation
     * 2. Rollback
     * 3. Catch exception
     */
    public function testRollbackNoActiveSimulation()
    {
        /**
         * 1. Check that there is no simulation
         */
        $this->assertEquals(0, count($this->em->getRepository('ActResourceBundle:Simulation')->findAll()));

        try {
            /**
             * 2. Rollback
             */
            $this->simulationService->rollback();

            // If no exception - test must fail
            $this->fail('An expected exception has not been raised.');
        } catch (\Exception $e) {
            /**
             * 3. Catch exception
             */
            $this->assertEquals("No active simulation found", $e->getMessage());
        }
    }

    /**
     * Test commit a simulation from a different user
     */
    public function testCommitSimulationFromOther()
    {
        $user = $this->em->getRepository('ApplicationSonataUserBundle:User')->find(2);
        $this->createSimulation($user, false);

        try {
            $this->simulationService->commit();
            $this->fail('An expected exception has not been raised.');
        } catch (\Exception $e) {
            $this->assertEquals('Not allowed to commit simulations from someone else', $e->getMessage());
        }
    }

    /**
     * Test rollback a simulation from a different user
     */
    public function testRollbackSimulationFromOther()
    {
        $user = $this->em->getRepository('ApplicationSonataUserBundle:User')->find(2);
        $this->createSimulation($user, false);

        try {
            $this->simulationService->rollback();
            $this->fail('An expected exception has not been raised.');
        } catch (\Exception $e) {
            $this->assertEquals('Not allowed to commit simulations from someone else', $e->getMessage());
        }
    }

    /**
     * Test try to access to a protected page during simulation
     * 1. Create a simulation launched by an other user
     * 2. Try to access the project planning
     * 3. Check we are redirected to homepage
     */
    public function testRedirectSimulation()
    {
        /**
         * 1. Create a simulation launched by an other user
         */
        $user = $this->em->getRepository('ApplicationSonataUserBundle:User')->find(2);
        $this->createSimulation($user, false);

        /**
         * 2. Try to access the project planning
         */
        $route = $this->client->getContainer()->get('router')->generate('act_resource_project_show', array('id' => 2));
        $this->client->request('GET', $route);

        /**
         * 3. Check we are redirected to homepage
         */
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Helper function to create a simulation
     * given a user object
     *
     * @param User $user
     * @param bool $addCredentials
     *
     * @return Simulation
     */
    private function createSimulation(User $user, $addCredentials = true)
    {
        // Create a new simulation
        $simulation = new Simulation();
        $simulation->setUser($user);
        $this->em->persist($simulation);
        $this->em->flush($simulation);

        /**
         * Check that the simulation is create
         */
        $this->assertEquals(1, count($this->em->getRepository('ActResourceBundle:Simulation')->findAll()));

        // Set the security token for the admin user
        if ($addCredentials) {
            $this->client->getContainer()->get('security.context')->setToken(new UsernamePasswordToken($user, null, 'main', $user->getRoles()));
        }

        return $simulation;
    }
}
