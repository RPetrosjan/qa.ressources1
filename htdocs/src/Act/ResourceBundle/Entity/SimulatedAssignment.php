<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SimulatedAssignement
 *
 * @ORM\Table(name="simulated_assignment")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class SimulatedAssignment
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="event", type="string", length=255)
     */
    private $event;

    /**
     * @var string
     *
     * @ORM\Column(name="serialized", type="text")
     */
    private $serialized;

    /**
     * @var integer the timestamp of creation with milliseconds
     * Note: we can't use datetime as milliseconds are not supported in MySQL
     *
     * @ORM\Column(type="bigint")
     */
    private $created;

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
     * Set event
     *
     * @param  string $event
     * @return SimulatedAssignement
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set serialized
     *
     * @param  string $serialized
     * @return SimulatedAssignement
     */
    public function setSerialized($serialized)
    {
        $this->serialized = $serialized;

        return $this;
    }

    /**
     * Get serialized
     *
     * @return string
     */
    public function getSerialized()
    {
        return $this->serialized;
    }

    /**
     * Returns the creation date
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Called by Doctrine when entity is created
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $time = explode(' ', microtime());
        $this->created = $time[1].($time[0]*100000000);
    }
}
