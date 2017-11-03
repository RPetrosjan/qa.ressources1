<?php

namespace Act\ResourceBundle\Services\Team;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Act\ResourceBundle\Entity\Team;
use Doctrine\ORM\EntityManager;
use Act\ResourceBundle\Services\Resource\ResourceManager;

/**
 * Class TeamManager
 *
 * Contains useful methods to manager team entities
 */
class TeamManager
{
    private $em;
    private $rm;
    private $sc;

    /**
     * Construct a new TeamManager
     * Inject the dependencies
     */
    public function __construct(EntityManager $em, ResourceManager $rm, SecurityContextInterface $sc)
    {
        $this->em = $em;
        $this->rm = $rm;
        $this->sc = $sc;
    }

    /**
     * Custom treatments before removing a team
     * @param Team $team
     */
    public function removeTeamDependencies(Team $team)
    {
        // Remove PreferedTeam entities
        foreach ($this->em->getRepository('ActResourceBundle:PreferedTeam')->getPreferedTeams($team) as $object) {
            $this->em->remove($object);
        }

        // Remove TeamProfile entities
        foreach ($this->em->getRepository('ActResourceBundle:TeamProfile')->findBy(array('team' => $team)) as $object) {
            $this->em->remove($object);
        }

        // Remove ProjectCpt entities
        foreach ($this->em->getRepository('ActResourceBundle:ProjectCpt')->findBy(array('team' => $team)) as $object) {
            $this->em->remove($object);
        }

        // Remove TeamProject entities
        foreach ($this->em->getRepository('ActResourceBundle:TeamProject')->findBy(array('team' => $team)) as $object) {
            $this->em->remove($object);
        }

        $this->em->flush();

        // If the current logged in user is linked to a resource of this team
        // It will fail to delete it - so the this user to null
        $user = $this->sc->getToken()->getUser();
        $resource = $this->em->getRepository('ActResourceBundle:Resource')->findOneBy(array('user' => $user));
        if ($resource != null) {
            // Set the user/resource to null at both sides
            $resource->setUser(null);
            $user->setResource(null);
            // Only flush these changes
            $this->em->flush($resource);
            $this->em->flush($user);
        }

        // Remove Resources entities
        foreach ($this->em->getRepository('ActResourceBundle:Resource')->getResourcesForThisTeam($team->getId())->getQuery()->getResult() as $resource) {
            $this->rm->removeResource($resource);
        }
    }
}
