<?php

namespace Act\ResourceBundle\Services\User;

use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContext;
use FOS\UserBundle\Doctrine\UserManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class FirstLogin
 *
 * Used for treatments that follow the first user login
 * in the application
 *
 */
class FirstLogin
{
    // Dependencies
    private $em;
    private $securityContext;
    private $userManager;
    private $session;
    private $translator;

    public function __construct(EntityManager $em, SecurityContext $sc, UserManager $fosUm, Session $session, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->securityContext = $sc;
        $this->userManager = $fosUm;
        $this->session = $session;
        $this->translator = $translator;
    }

    /**
     * Returns all available resources
     * @return Array
     */
    public function getAvailableResources()
    {
        return $this->em->getRepository('ActResourceBundle:Resource')->getResourcesWithNoUser();
    }

    /**
     * Checks if the logged in user needs a resource to be
     * linked to his account before going on further.
     */
    public function userNeedsResource()
    {
        $result = false;

        $token = $this->securityContext->getToken();
        if (isset($token)) {
            $user = $token->getUser();
            if ($user instanceof User) {
                if ($user->getResource() == null) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     * Link an existing resource to an new user
     */
    public function linkResource($id)
    {
        // Get the resource
        $resource = $this->em->getRepository('ActResourceBundle:Resource')->find($id);
        if (!$resource) {
          return false;
        }

        $user = $this->securityContext->getToken()->getUser();
        if (!$user) {
            return false;
        }

        // Check that the resource is not already linked to someone else
        if ($resource->getUser()) {
            return false;
        }

        // Set the link on both user and resource
        $resource->setUser($user);
        $user->setResource($resource);

        // Save both user and resource
        $this->userManager->updateUser($user);
        $this->em->persist($resource);
        $this->em->flush();
    }
}
