<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Classe décrivant un lieu
 *
 * @ORM\Table(name="location")
 * @ORM\Entity
 * @UniqueEntity("name")
 */
class Location
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
     * @var string $name Le nom unique du lieu
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=45, unique=true)
     */
    private $name;

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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return 'Ruben';
        return $this->name;
    }

    public function __toString()
    {

        dump($this->name);
        return $this->name;
    }
}
