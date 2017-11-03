<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Application\Sonata\UserBundle\Entity\User as AppUser;

/**
 * Décrit une simulation sur l'application
 * Une seule simulation est possible à un instant donné
 *
 * @ORM\Table(name="simulation")
 * @ORM\Entity
 */
class Simulation
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
     * Date de démarrage de la simulation
     * @var datetime $start
     *
     * @ORM\Column(name="start", type="datetime")
     */
    private $start;

    /**
     * Utilisateur ayant lancé la simulation, une seule simulation par utilisateur
     * @var User $user
     *
     * @ORM\OneToOne(targetEntity="Application\Sonata\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private $user;

    public function __construct()
    {
        $this->start = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(AppUser $u)
    {
        $this->user = $u;

        return $this;
    }

    /**
     * Set start
     *
     * @param datetime $start
     */
    public function setStart(\DateTime $start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return datetime
     */
    public function getStart()
    {
        return $this->start;
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
}
