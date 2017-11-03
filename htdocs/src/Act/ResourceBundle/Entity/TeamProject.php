<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Classe décrivant un projet lié à une équipe pour l'affichage
 * On peut choisir plusieurs projects à lier à une équipe par utilisateur
 *
 * @ORM\Table(name="team_project", uniqueConstraints={@ORM\UniqueConstraint(name="team_user_projects", columns={"user_id", "team_id"})})
 * @ORM\Entity(repositoryClass="Act\ResourceBundle\Entity\TeamProjectRepository")
 */
class TeamProject
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Team $team l'équipe concernée
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Team")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id", nullable=false)
     */
    private $team;

    /**
     * @var User $user l'utilisateur concerné
     *
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * @var ArrayCollection $projects Les projets liés à l'équipe/utilisateur
     *
     * @ORM\ManyToMany(targetEntity="Act\ResourceBundle\Entity\Project", inversedBy="teamProjects")
     * @ORM\JoinTable(name="team_project_item",
     *  joinColumns={@ORM\JoinColumn(name="project_id", referencedColumnName="id")},
     *  inverseJoinColumns={@ORM\JoinColumn(name="team_project_id", referencedColumnName="id")}
     * )
     */
    private $projects;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->projects = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set team
     *
     * @param  \Act\ResourceBundle\Entity\Team $team
     * @return TeamProject
     */
    public function setTeam(\Act\ResourceBundle\Entity\Team $team)
    {
        $this->team = $team;

        return $this;
    }

    /**
     * Get team
     *
     * @return \Act\ResourceBundle\Entity\Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Set user
     *
     * @param  \Application\Sonata\UserBundle\Entity\User $user
     * @return TeamProject
     */
    public function setUser(\Application\Sonata\UserBundle\Entity\User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Application\Sonata\UserBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add project
     *
     * @param  \Act\ResourceBundle\Entity\Project $project
     * @return TeamProject
     */
    public function addProject(\Act\ResourceBundle\Entity\Project $project)
    {
        $this->projects[] = $project;

        return $this;
    }

    /**
     * Remove project
     *
     * @param \Act\ResourceBundle\Entity\Project $project
     */
    public function removeProject(\Act\ResourceBundle\Entity\Project $project)
    {
        $this->projects->removeElement($projects);
    }

    /**
     * Clear all the projects
     */
    public function clearProjects()
    {
        $this->projects = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get projects
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjects()
    {
        return $this->projects;
    }
}
