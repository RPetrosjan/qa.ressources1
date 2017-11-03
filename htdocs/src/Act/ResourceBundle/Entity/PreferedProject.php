<?php

namespace Act\ResourceBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Classe décrivant un projet préféré
 *
 * @ORM\Table(name="prefered_project")
 * @ORM\Entity(repositoryClass="Act\ResourceBundle\Entity\PreferedProjectRepository")
 */
class PreferedProject
{
    /**
     * @var Project $project le projet concerné
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Project", inversedBy="preferedProjects")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    private $project;

    /**
     * @var User $user l'utilisateur concerné
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User", inversedBy="preferedProjects")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * Au cas où on devra à nouveau gérer la position un jour...
     *
     * @Gedmo\SortablePosition
     * @ORM\Column(name="position", type="integer", nullable=true)
     */
    private $position;

    /**
     * Set project
     * @param Act\ResourceBundle\Entity\Project $project
     */
    public function setProject(\Act\ResourceBundle\Entity\Project $project)
    {
        $this->project = $project;
    }

    /**
     * Get project
     * @return Act\ResourceBundle\Entity\Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set user
     * @param \Application\Sonata\UserBundle\Entity\User $user
     */
    public function setUser(\Application\Sonata\UserBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     * @return \Application\Sonata\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }
}
