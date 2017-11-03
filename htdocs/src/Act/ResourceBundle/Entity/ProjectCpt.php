<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ProjectCpt class
 *
 * Describe a technical project manager
 * It's a resource, for a given team, for a given project
 *
 * @ORM\Table(name="project_cpt", uniqueConstraints={@ORM\UniqueConstraint(name="cpt_project_team", columns={"project_id", "team_id"})})
 * @ORM\Entity
 * @UniqueEntity({"project", "team"})
 */
class ProjectCpt
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
     * @var Project $project the project
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Project", inversedBy="cpts")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    private $project;

    /**
     * @var Resource $resource the resource
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Resource")
     * @ORM\JoinColumn(name="resource_id", referencedColumnName="id", nullable=true)
     */
    private $resource;

    /**
     * @var Team $team the team
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Team", inversedBy="cpts")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id", nullable=false)
     */
    private $team;

    /**
     * Get the id
     * @codeCoverageIgnore
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the project
     * @codeCoverageIgnore
     * @param Project $project
     */
    public function setProject(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Get the project
     * @codeCoverageIgnore
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set the resource
     * @codeCoverageIgnore
     * @param Resource $resource
     */
    public function setResource(Resource $resource = null)
    {
        $this->resource = $resource;
    }

    /**
     * Get the resource
     * @codeCoverageIgnore
     * @return Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set the team
     * @codeCoverageIgnore
     * @param Team $team
     */
    public function setTeam(Team $team)
    {
        $this->team = $team;
    }

    /**
     * Get the team
     * @codeCoverageIgnore
     * @return Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Display as a string
     * @codeCoverageIgnore
     * @return String
     */
    public function __toString()
    {
        if ($this->resource) {
            return 'CPT : '.$this->resource->getNameShort().' ('.$this->project->getNameShort().')';
        }

        return "CPT";
    }
}
