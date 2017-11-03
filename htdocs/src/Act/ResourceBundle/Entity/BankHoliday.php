<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContext;

/**
 * Bankholiday Entity
 *
 * Describe a bankholiday with a location and a unique key on name and date
 *
 * @ORM\Table(name="bank_holiday", uniqueConstraints={@ORM\UniqueConstraint(name="bankholiday_name_date", columns={"name", "start"})})
 * @ORM\Entity(repositoryClass="Act\ResourceBundle\Entity\BankHolidayRepository")
 * @UniqueEntity({"name", "start"})
 * @Assert\Callback(methods={"isLocationsValid"})
 *
 */
class BankHoliday
{
    /**
     * Clé primaire
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * Date du jour férié
     * @var DateTime la date du jour férié
     *
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     * @ORM\Column(name="start", type="date")
     */
    private $start;

    /**
     * Nom du jour férié
     * @var string $name le nom du jour férié
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=45)
     */
    private $name;

    /**
     * Lieux où ce jour férié se déroule
     * @var ArrayCollection les lieux de ce jour férié
     *
     * @Assert\NotNull()
     * @ORM\ManyToMany(targetEntity="Act\ResourceBundle\Entity\Location")
     */
    private $locations;

    /**
     * Constructeur des objets jours fériés
     * Par défaut la durée est de 1 jour.
     * NB: Pour le moment cette donnée de durée n'est pas utilisée !
     */
    public function __construct()
    {
        $this->locations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set start
     *
     * @param DateTime $start
     */
    public function setStart(\DateTime $start = null)
    {
        if ($start != null) {
            $start->setTime(0,0,0);
            $this->start = clone $start;
        } else {
            $this->start = null;
        }
    }

    /**
     * Get start
     *
     * @return DateTime
     */
    public function getStart()
    {
        if($this->start != null)

            return clone $this->start;
        else
            return null;
    }

    /**
     * Alias for getStart()
     */
    public function getDay()
    {
        return $this->getStart();
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
        return $this->name;
    }

    /**
     * Renvoi la liste des lieux de ce jour férié
     *
     * @return ArrayCollection
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * Idem que celle ci-dessus mais Sonata demande un getLocation
     * et non pas un getLocationS
     *
     * @return ArrayCollection
     */
    public function getLocation()
    {
        return $this->getLocations();
    }

    /**
     * Ajoute un lieu à ce jour férié
     * @param Location $location
     */
    public function addLocation(\Act\ResourceBundle\Entity\Location $location)
    {
        $this->locations[] = $location;
    }

    /**
    * Ajoute un lieu à ce jour férié
    * @param Location $location
    */
    public function setLocations(\Act\ResourceBundle\Entity\Location $location)
    {
        $this->addLocation($location);
    }

    /**
     * Affichage de l'objet en tant que chaîne de caractères
     * @return String
     */
    public function __toString()
    {
        if ($this->start != null)
            $date = $this->start;
        else
            $date = new \DateTime('2010-01-01');

        return $date->format('d/m/Y').' - '.$this->name.'';
    }

    /**
     * Remove locations
     * @param Location $locations
     */
    public function removeLocation(\Act\ResourceBundle\Entity\Location $locations)
    {
        $this->locations->removeElement($locations);
    }

    /**
     * Callback function for validation
     *
     * @param ExecutionContext $context
     */
    public function isLocationsValid(ExecutionContext $context)
    {
        if ($this->locations->count() == 0) {
            $context->addViolationAtSubPath('locations', 'Selectionnez au moins un lieu', array(), null);
        }
    }

    /**
     * Determines if the bankholiday takes place during a working day
     *
     * @return bool
     */
    public function isWorkingDay()
    {
        return ($this->start->format('N') < 6);
    }
}
