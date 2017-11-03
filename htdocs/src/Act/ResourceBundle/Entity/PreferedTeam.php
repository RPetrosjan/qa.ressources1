<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Sonata\UserBundle\Entity\User as AppUser;

/**
 * Classe décrivant une équipe préférée
 * On peut choisir plusieurs équipes préférées par projet
 * NB: les équipes préférées seront chargées directement (alors que les autres en AJAX si voulu)
 *
 * @ORM\Table(name="prefered_team")
 * @ORM\Entity(repositoryClass="Act\ResourceBundle\Entity\PreferedTeamRepository")
 */
class PreferedTeam
{
    /**
     * @var Project $project le projet pour lequel on choisi une équipe préférée
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Project", inversedBy="preferedTeams")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    private $project;

    /**
     * @var Team $team l'équipe préférée (à charger directement)
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Team", inversedBy="preferedTeams")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id", nullable=false)
     */
    private $team;

    /**
     * @var AppUser $user l'utilisateur concerné
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Application\Sonata\UserBundle\Entity\User", inversedBy="preferedTeams")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    /**
     * Set project
     *
     * @param  \Act\ResourceBundle\Entity\Project $project
     * @return PreferedTeam
     */
    public function setProject(Project $project)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project
     *
     * @return \Act\ResourceBundle\Entity\Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set team
     *
     * @param  \Act\ResourceBundle\Entity\Team $team
     * @return PreferedTeam
     */
    public function setTeam(Team $team)
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
     * @return PreferedTeam
     */
    public function setUser(AppUser $user)
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
}
