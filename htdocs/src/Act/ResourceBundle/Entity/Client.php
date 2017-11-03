<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Client Entity
 *
 * @ORM\Table(name="client")
 * @ORM\Entity(repositoryClass="Act\ResourceBundle\Entity\ClientRepository")
 * @UniqueEntity("name")
 * @UniqueEntity("nameShort")
 */
class Client
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
     * @var string $name the client name
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=45, unique=true)
     */
    private $name;

    /**
     * @var string $nameShort the client code
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name_short", type="string", length=10, unique=true)
     */
    private $nameShort;

    /**
     * @var string $contactName the name of the contact client side
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="contact_name", type="string")
     */
    private $contactName;

    /**
     * Get id
     * @codeCoverageIgnore
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     * @codeCoverageIgnore
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     * @codeCoverageIgnore
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name short
     * @codeCoverageIgnore
     * @param string $nameShort
     */
    public function setNameShort($nameShort)
    {
        $this->nameShort = $nameShort;
    }

    /**
     * Get name short
     * @codeCoverageIgnore
     * @return string
     */
    public function getNameShort()
    {
        return $this->nameShort;
    }

    /**
     * Set contact name
     * @codeCoverageIgnore
     * @param string $contactName
     */
    public function setContactName($contactName)
    {
        $this->contactName = $contactName;
    }

    /**
     * Get contact name
     * @codeCoverageIgnore
     * @return string
     */
    public function getContactName()
    {
        return $this->contactName;
    }

    /**
     * Display as a string
     * @codeCoverageIgnore
     * @return String
     */
    public function __toString()
    {
        return $this->name.' ('.$this->nameShort.')';
    }
}
