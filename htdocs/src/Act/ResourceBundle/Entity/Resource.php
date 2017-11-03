<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Classe décrivant une ressource
 * Clé unique sur deux champs : nom et code
 *
 * @ORM\Table(name="resource")
 * @ORM\Entity(repositoryClass="Act\ResourceBundle\Entity\ResourceRepository")
 * @UniqueEntity("name")
 * @UniqueEntity("nameShort")
 */
class Resource
{
    /**
     * Clé primaire
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $name
     *  Le nom de la ressource - unique
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=45, unique = true)
     */
    private $name;

    /**
     * @var string $nameShort
     *  Le code de la ressource - unique
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name_short", type="string", length=10, unique = true)
     */
    private $nameShort;

    /**
     * @var DateTime
     *  La date d'embauche
     *
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     * @ORM\Column(name="start", type="date")
     */
    private $start;

    /**
     * @var DateTime
     *  La date de départ - peut être nulle
     *
     * @Assert\Type("\DateTime")
     * @ORM\Column(name="end", type="date", nullable=true)
     */
    private $end;

    /**
     * @var float $days_per_week
     *  Le nombre de jours travaillés par semaine
     *
     * @Assert\Type("numeric")
     * @Assert\Range(
     *      min = "0",
     *      max = "10"
     * )
     * @ORM\Column(name="days_per_week", type="decimal", scale=2)
     */
    private $days_per_week;

    /**
     * @var Team $team
     *  L'équipe à laquelle appartient cette ressource
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Team", inversedBy="resources")
     * @ORM\JoinColumn(name="team_id", referencedColumnName="id", nullable=false)
     */
    private $team;

    /**
     * @var User $user
     *  L'utilisateur lié à cette ressource - peut être nul
     *
     * @ORM\OneToOne(targetEntity="Application\Sonata\UserBundle\Entity\User", inversedBy="resource")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $user;

    /**
     * @var Location $location
     *  Le lieu où travaille cette ressource, pour gérer les jours fériés
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Location")
     * @ORM\JoinColumn(name="location_id", referencedColumnName="id", nullable=false)
     */
    private $location;

    /**
     * @var ArrayCollection $assignments
     *  La liste de toutes les affectations de cette ressource
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\Assignment", mappedBy="resource", cascade={"all"})
     */
    private $assignments;

    /**
     * @var string $information
     *   A free text for describing the resource.
     *
     * @ORM\Column(name="information", type="text", nullable=true)
     */
    private $information;

    /**
     * Constructeur de l'objet Resource
     * Par défaut, une resource est à temps plein : 5jrs/semaine
     */
    public function __construct()
    {
        $this->days_per_week = 5.;
        $this->assignments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Récupère les affectations de cette ressource
     * Si $day est donnée, on ne garde que les affectations qui ont lieu ce jour
     *
     * @param  \DateTime       $day ne prend que les affectations de ce jour là
     * @return ArrayCollection
     */
    public function getAssignments(\DateTime $day = null)
    {
        $result = array();
        if ($day != null) {
            $day->setTime(0,0,0);
            foreach ($this->assignments as $a) {
                if ($a->getDay() == $day) {
                    $result[] = $a;
                }
            }

            return $result;
        } else {
            return $this->assignments;
        }
    }

    /**
     * Récupère le total des affectations de cette ressource
     * Si $day est donnée, on ne garde que les affectations qui ont lieu ce jour
     *
     * @param  \DateTime $day ne prend que les affectations de ce jour là
     * @return float
     */
    public function getTotalAssigned(\DateTime $day = null)
    {
        $result = 0;

        foreach ($this->assignments as $a) {
            if ($day != null) {
                if ($a->getDay()->format('d/m/Y') == $day->format('d/m/Y')) {
                    $result += $a->getWorkload();
                }
            } else {
                $result += $a->getWorkload();
            }
        }

        return $result;
    }

    /**
     * Renvoi l'affectation qui a lieu ce jour et dans ce projet, ou null si inexistante
     *
     * @param  Project         $project
     * @param  DateTime        $day
     * @return Assignment/null
     */
    public function getAssignment(\Act\ResourceBundle\Entity\Project $project, \DateTime $day)
    {
        $day->setTime(0,0,0);
        foreach ($this->assignments as $assignment) {
            if($assignment->getProject() == $project && $assignment->getDay() == $day)

                return $assignment;
        }

        return null;
    }

    /**
     * Ajoute une affectation à cette ressource
     * @param \Act\ResourceBundle\Entity\Assignment $a
     */
    public function addAssignment(\Act\ResourceBundle\Entity\Assignment $a)
    {
        $this->assignments[] = $a;
    }

    /**
     * Renvoi l'équipe de la ressource
     * @return Team
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * Défini l'équipe de la ressource
     * @param \Act\ResourceBundle\Entity\Team $team
     */
    public function setTeam(\Act\ResourceBundle\Entity\Team $team = null)
    {
        $this->team = $team;
    }

    /**
     * Renvoi l'utilisateur lié à cette ressource
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Défini l'utilisateur lié à cette ressource
     * @param \Application\Sonata\UserBundle\Entity\User $user
     */
    public function setUser(\Application\Sonata\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;
    }

    /**
     * Retourne les roles de l'user linker a la ressource
     * @return array
    */
    public function getRoleUser()
    {
        if ($this->user != null) {
          return $this->user->getRoles();
        }
    }

     /**
     * Défini si l'utilisateur lié à cette ressource est un manager
     * @return booleen
     */
    public function isManager()
    {
        if ($this->user != null) {
            if ($this->user->hasRole("ROLE_ADMIN") || $this->user->hasRole("ROLE_RP")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Défini si l'utilisateur lié à cette ressource est un manager
     * @return booleen
     */
    public function isCPF()
    {
        if ($this->user != null) {
            if ($this->user->hasRole("ROLE_ADMIN") || $this->user->hasRole("ROLE_CPF")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Renvoi le lieu de travail de la ressource
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Défini le lieu de travail de cette ressource
     * @param \Act\ResourceBundle\Entity\Location $location
     */
    public function setLocation(\Act\ResourceBundle\Entity\Location $location)
    {
        $this->location = $location;
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
     * Set name short
     *
     * @param string $nameShort
     */
    public function setNameShort($nameShort)
    {
        $this->nameShort = $nameShort;
    }

    /**
     * Get name short
     *
     * @return string
     */
    public function getNameShort()
    {
        return $this->nameShort;
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
     * @return date
     */
    public function getStart()
    {
        if($this->start == null)

            return null;
        else
            return clone $this->start;
    }

    /**
     * Set end
     *
     * @param DateTime $end
     */
    public function setEnd(\DateTime $end = null)
    {
        if ($end != null) {
            $end->setTime(0,0,0);
            $this->end = clone $end;
        } else {
            $this->end = null;
        }
    }

    /**
     * Get end
     *
     * @return DateTime
     */
    public function getEnd()
    {
        if($this->end == null)

            return null;
        else
            return clone $this->end;
    }

    /**
     * Set days_per_week
     * NB: Ne peut être inférieur à 0
     *
     * @param decimal $daysPerWeek
     */
    public function setDaysPerWeek($daysPerWeek)
    {
        if(strpos($daysPerWeek, ',') !== false)
            $daysPerWeek = str_replace(',', '.', $daysPerWeek);

        if($daysPerWeek < 0)
            $this->days_per_week = 0;
        else
            $this->days_per_week = (float) $daysPerWeek;
    }

    /**
     * Get days_per_week
     *
     * @return decimal
     */
    public function getDaysPerWeek()
    {
        return (float) $this->days_per_week;
    }

    /**
     * Affichage en tant que chaîne de caractères
     * @return String
     */
    public function __toString()
    {
        return $this->nameShort.' - '.$this->name;
    }

    /**
     * Renvoi le workload total de la semaine
     * Si la ressource n'est plus présente dans l'entreprise à ce moment la, renvoi le temps maximal où elle peut être employée "Fully Booked"
     * @return float
     */
    public function getWeekWorkload(\DateTime $start, \DateTime $end)
    {
        $week = $start->format('W');
        $result = 0;
        // 1. La ressource n'est plus dans l'entreprise à cette date
        if (!$this->isAvailable($start, $end)) {
            return $this->days_per_week; // Fully Booked
        }
        // 2. La ressource quitte l'entreprise durant la semaine
        elseif ($this->isLeavingInDates($start, $end)) {
            foreach ($this->assignments as $a) {
                // Toutes les affectations précédant le départ de la ressource
                if ($a->getDay() >= $start && $a->getDay() <= $this->end && $a->getProject() && $a->getProject()->isActive() == 1) {
                    $result += $a->getWorkload();
                }
            }
            // On ajoute les jours où la ressource n'est plus présente
            $nbDaysToAdd = 0;
            $tmp = clone $this->end;
            $tmp->add(new \DateInterval('P1D'));
            while ($tmp->format('W') == $week) {
                if($tmp->format('N') > 0 && $tmp->format('N') < 6)
                    $nbDaysToAdd++;
                $tmp->add(new \DateInterval('P1D'));
            }
            $result += $nbDaysToAdd;
        }
        // 3. La ressource arrive dans l'entreprise durant la semaine
        elseif ($this->isComingInDates($start, $end)) {
            $result = 0;
            foreach ($this->assignments as $a) {
                // Toutes les affectations précédant le départ de la ressource
                if ($a->getDay() >= $this->start && $a->getDay() < $end && $a->getProject() && $a->getProject()->isActive() == 1) {
                    $result += $a->getWorkload();
                }
            }
            // On ajoute les jours où la ressource n'est plus présente
            $nbDaysToAdd = 0;
            $tmp = clone $this->start;
            $tmp->sub(new \DateInterval('P1D'));
            while ($tmp->format('W') == $week) {
                if($tmp->format('N') > 0 && $tmp->format('N') < 6)
                    $nbDaysToAdd++;
                $tmp->sub(new \DateInterval('P1D'));
            }
            $result += $nbDaysToAdd;
        }
        // 4. Pas de soucis on ne fait que compter les affectations
        else {
            $result = 0;
            foreach ($this->assignments as $a) {
                if ($a->getDay() >= $start && $a->getDay() < $end && $a->getProject() && $a->getProject()->isActive() == 1) {
                    $result += $a->getWorkload();
                }
            }
        }

        return $result;
    }

    /**
     * Determines if the resource is available at least one day in the date period
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return boolean
     */
    public function isAvailable(\DateTime $start, \DateTime $end)
    {
        if ($this->start > $end || ($this->end != null && $this->end < $start)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Returns true if the resource if leaving in the date period
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return bool
     */
    public function isLeavingInDates(\DateTime $start, \DateTime $end)
    {
        return ($this->end >= $start && $this->end <= $end);
    }

    /**
     * Returns true if the resource if arriving in the date period
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return bool
     */
    public function isComingInDates(\DateTime $start, \DateTime $end)
    {
        return ($this->start >= $start && $this->start <= $end);
    }

    /**
     * Renvoi le workload total de la semaine, pour le projet donné
     *
     * @param Project $p
     * @param $week la semaine concernée
     * @param $year l'année de la semaine
     *
     * @return float
     */
    public function getProjectWeekWorkload(Project $p, $week, $year)
    {
        $result = 0;
        foreach ($this->assignments as $a) {
            if ($a->getDay()->format('W') == $week && $a->getProject() && $a->getProject() == $p) {
                // On vérifie aussi que c'est la même année, sauf pour les semaines limitropes, ou cela peut être l'année précédente
                if (($a->getDay()->format('Y') == $year) || ($week == 1 && $a->getDay()->format('Y') == $year-1)) {
                    $result += $a->getWorkload();
                }
            }
        }

        return $result;
    }

    /**
     * Renvoi les projets liés aux affectations de la ressource
     * @return Array
     */
    public function getProjects()
    {
        $result = array();
        foreach ($this->assignments as $a) {
            if(!isset($result[$a->getProject()->getId()]))
                $result[$a->getProject()->getId()] = $a->getProject();
        }

        return $result;
    }

    /**
     * Supprime une affectation
     * @param Assignment $assignment
     */
    public function removeAssignment(\Act\ResourceBundle\Entity\Assignment $assignment)
    {
        $this->assignments->removeElement($assignment);
    }

    /**
     * Get information for this resource.
     *
     * @return string
     */
    public function getInformation()
    {
        return $this->information;
    }

    /**
     * Set information for this resource.
     *
     * @param string $info
     */
    public function setInformation($info = null)
    {
        $this->information = $info;
    }
}
