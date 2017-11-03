<?php

namespace Act\ResourceBundle\Entity;

use Act\ResourceBundle\Entity\PreferedTeam;
use Act\ResourceBundle\Entity\ProjectCpt;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection as ArrayCollection;

/**
 * Classe décrivant une équipe
 * Clé unique sur le nom
 *
 * @ORM\Table(name="team")
 * @ORM\Entity(repositoryClass="Act\ResourceBundle\Entity\TeamRepository")
 * @UniqueEntity("name")
 */
class Team
{
    /**
     * Clé primaire
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $name Le nom de l'équipe - unique
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=45, unique=true)
     */
    private $name;

    /**
     * @var string $color La couleur de l'équipe
     *
     * @Assert\NotBlank()
     * @Assert\Regex("/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/")
     * @ORM\Column(name="color", type="string", length=7)
     */
    private $color;

    /**
     * @var Resource $resource Le manager de l'équipe - peut être nul
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Resource")
     * @ORM\JoinColumn(name="resource_id", referencedColumnName="id", nullable=true)
     */
    private $manager;

    /**
     * @var ArrayCollection $resources Les ressources membres de cette équipe
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\Resource", mappedBy="team", cascade={"ALL"})
     */
    private $resources;

    /**
     * @var ArrayCollection $profiles les profils d'équipes
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\TeamProfile", mappedBy="team", cascade={"ALL"})
     */
    private $profiles;

    /**
     * @var ArrayCollection $previsionalSubscribers les utilisateurs souhaitant recevoir une
     * notification de remplissage de cette équipe à intervale régulier.
     *
     * @ORM\ManyToMany(targetEntity="Application\Sonata\UserBundle\Entity\User", mappedBy="previsionalTeams", cascade={"remove"})
     */
    private $previsionalSubscribers;

    /**
     * @var ArrayCollection $preferedTeams équipes préférées liées
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\PreferedTeam", mappedBy="team", cascade={"remove"})
     */
    private $preferedTeams;

    /**
     * @var ArrayCollection $cpts
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\ProjectCpt", mappedBy="team", cascade={"remove"})
     */
    private $cpts;

    public function __construct()
    {
        $this->resources = new \Doctrine\Common\Collections\ArrayCollection();
        $this->profiles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->preferedTeams = new \Doctrine\Common\Collections\ArrayCollection();
        $this->cpts = new \Doctrine\Common\Collections\ArrayCollection();

        // génération couleur aléatoire
        $a = dechex(mt_rand(0,15));
        $b = dechex(mt_rand(0,15));
        $c = dechex(mt_rand(0,15));
        $d = dechex(mt_rand(0,15));
        $e = dechex(mt_rand(0,15));
        $f = dechex(mt_rand(0,15));
        $this->color = '#'. $a . $b . $c . $d . $e . $f;
    }

    /**
     * Renvoi les profils
     * @return ArrayCollection
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * Ajoute un profil
     * @param TeamProfile $tp
     */
    public function addProfile(\Act\ResourceBundle\Entity\TeamProfile $tp)
    {
        $this->profiles[] = $tp;
    }

    /**
     * Renvoi les utilisateurs ayant souscrit aux notifications email du
     * remplissage de cette équipe.
     * @return ArrayCollection
     */
    public function getPrevisionalSubscribers()
    {
        return $this->previsionalSubscribers;
    }

    /**
     * Ajoute un utilisateur à la liste des utilisateurs ayant souscrit aux
     * notifications email de remplissage de cette équipe.
     * @param User $u
     */
    public function addPrevisionalSubscriber(\Application\Sonata\UserBundle\Entity\User $u)
    {
        $this->previsionalSubscribers[] = $u;
    }

    /**
     * Renvoi les ressources de l'équipe
     * @return ArrayCollection
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Ajoute une ressource à l'équipe
     * @param \Act\ResourceBundle\Entity\Resource $r
     */
    public function addResource(\Act\ResourceBundle\Entity\Resource $r)
    {

        $this->resources->add($r);
    }

    /**
     * Défini les ressources de l'équipe
     * @param array $resources
     */
    public function setResources(array $resources = null)
    {
        $this->resources->clear();
        if ($resources != null) {
            foreach ($resources as $r) {
                $this->addResource($r);
            }
        }
    }

    /**
     * Renvoi le manager de l'équipe
     * @return Resource
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * Défini le manager de l'équipe
     * @param \Act\ResourceBundle\Entity\Resource $resource
     */
    public function setManager(\Act\ResourceBundle\Entity\Resource $resource = null)
    {
        $this->manager = $resource;
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
     * Set color
     *
     * @param string $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * Get color
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Affichage en tant que chaîne de caractère
     * @return String
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Renvoi le nombre de ressources membres
     * @return int
     */
    public function getNbResources()
    {
        return count($this->resources);
    }

    /**
     * Renvoi la liste des projets sur lesquels les ressources membres travaillent
     * @return Array
     */
    public function getProjects()
    {
        $result = array();
        foreach ($this->resources as $r) {
            foreach ($r->getAssignments() as $a) {
                $project = $a->getProject();
                if (!isset($result[$project->getId()])) {
                    $result[$project->getId()] = $project;
                }
            }
        }

        return $result;
    }

    /**
     * Remove resource
     * @param Resource $resource
     */
    public function removeResource(\Act\ResourceBundle\Entity\Resource $resource)
    {
        $this->resources->removeElement($resource);
    }

    /**
     * Remove profile
     *
     * @param \Act\ResourceBundle\Entity\TeamProfile $profiles
     */
    public function removeProfile(\Act\ResourceBundle\Entity\TeamProfile $profile)
    {
        $this->profiles->removeElement($profile);
    }

    /**
     * Supprime un utilisateur de la liste des utilisateurs ayant souscrit aux
     * notifications email de remplissage de cette équipe.
     *
     */
    public function removePrevisionalSubscriber($valueToDelete)
    {
        if (($key = array_search($valueToDelete, $this->previsionalSubscribers)) !== false) {
            unset($this->previsionalSubscribers[$key]);
        }
    }

    /**
     * Returns the total workload for all the team
     * for the given week dates
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return float
     */
    public function getTotalWorkload(\DateTime $start, \DateTime $end)
    {
        $workload = 0;

        foreach ($this->resources as $resource) {
            $workload += $resource->getDaysPerWeek();
        }

        return $workload;
    }

    /**
     * Return the available workload for all the team
     * for the given week dates
     *
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return float
     */
    public function getWorkloadAvailable(\DateTime $start, \DateTime $end)
    {
        $workload = 0;

        foreach ($this->resources as $resource) {
            $workload += $resource->getWeekWorkload($start, $end);
        }

        return ($this->getTotalWorkload($start, $end) - $workload);
    }

    public function setPrevisionalSubscribers($prevSub = null)
    {
        $this->previsionalSubscribers = $prevSub;
    }

    public function getPreferedTeams()
    {
        return $this->preferedTeams;
    }

    public function addPreferedTeam(PreferedTeam $pt)
    {
        $this->preferedTeams[] = $pt;
    }

    public function removePreferedTeam(PreferedTeam $pt)
    {
        $this->preferedTeams->removeElement($pt);
    }

    public function getCpts()
    {
        return $this->cpts;
    }

    public function addCpt(ProjectCpt $cpt)
    {
        $this->cpts[] = $cpt;
    }

    public function removeCpt(ProjectCpt $cpt)
    {
        $this->cpts->removeElement($cpt);
    }
}
