<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Act\ResourceBundle\Entity\TeamProfile
 *
 * @ORM\Table(name="team_profile")
 * @ORM\Entity
 * @UniqueEntity("name")
 */
class TeamProfile
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
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @var Team $team
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Team", inversedBy="profiles")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id", nullable=false)
     */
    private $team;

    /**
     * Renvoi l'équipe liée
     * @return Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Défini l'équipe liée
     * @param \Act\ResourceBundle\Entity\Team $t
     */
    public function setTeam(\Act\ResourceBundle\Entity\Team $t)
    {
        $this->team = $t;
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
     * Set name
     *
     * @param  string      $name
     * @return TeamProfile
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the object as a string
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
