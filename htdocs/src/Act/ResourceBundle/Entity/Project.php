<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Act\MainBundle\Validator\Constraints as ActAssert;

/**
 * Entity Project
 *
 * Describe a project in the application
 * Two unique keys : on the name, and on the code
 *
 * @ORM\Table(name="project")
 * @ORM\Entity(repositoryClass="Act\ResourceBundle\Entity\ProjectRepository")
 * @UniqueEntity("name")
 * @UniqueEntity("nameShort")
 * @ActAssert\DateRange(start="getStart", end="getEnd")
 */
class Project
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
     * @var string $name the project name
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=45, unique=true)
     */
    private $name;

    /**
     * @var string $nameShort the project code name
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name_short", type="string", length=10, unique=true)
     */
    private $nameShort;

    /**
     * @var boolean $active is the project enabled ?
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * @var string $color the project color in hexadecimal
     *
     * @Assert\NotBlank()
     * @Assert\Regex("/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/")
     * @ORM\Column(name="color", type="string", length=7)
     */
    private $color;

    /**
     * @var boolean $typePresaleGT70 is the project a presale with greater chances than 70% ?
     *
     * @ORM\Column(name="is_presale_gt70", type="boolean")
     */
    private $typePresaleGT70;


    /**
     * @var boolean $project_signee bollean variable for indication signed Project (yes/no) ?
     *
     * @ORM\Column(name="project_signee", type="boolean")
     */
    private $project_signee;

    /**
     * @var boolean $typePresaleLT70 is the project a presale with lower chances than 70% ?
     *
     * @ORM\Column(name="is_presale_lt70", type="boolean")
     */
    private $typePresaleLT70;

    /**
     * @var boolean $typeSigned is the project signed ?
     *
     * @ORM\Column(name="is_signed", type="boolean")
     */
    private $typeSigned;

    /**
     * @var boolean $typeHoliday is the project a holiday project ?
     *
     * @ORM\Column(name="is_holiday", type="boolean")
     */
    private $typeHoliday;

    /**
     * @var boolean $typeInternal is the project an internal project ?
     *
     * @ORM\Column(name="is_internal", type="boolean")
     */
    private $typeInternal;

    /**
     * @var boolean $typeResearch is the project a R&D project ?
     *
     * @ORM\Column(name="is_research", type="boolean")
     */
    private $typeResearch;

    /**
     * @var Client $client the project client
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id", nullable=true)
     */
    private $client;

    /**
     * @var Resource $cpf the project functional project manager
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Resource")
     * @ORM\JoinColumn(name="resource_id", referencedColumnName="id", nullable=true)
     */
    private $cpf;

    /**
     * @var ArrayCollection $comments comments linked to this project
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\Comment", mappedBy="project", cascade={"remove"})
     */
    private $comments;

    /**
     * @var ArrayCollection $cpts the project technical project manager
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\ProjectCpt", mappedBy="project", cascade={"remove"})
     */
    private $cpts;

    /**
     * @var ArrayCollection $links the project links
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\Link", mappedBy="project", cascade={"remove"})
     */
    private $links;

    /**
     * @var ArrayCollection $tasks the project tasks
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\Task", mappedBy="project", cascade={"remove"})
     */
    private $tasks;

    /**
     * @var ArrayCollection $assignments the project assignments
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\Assignment", mappedBy="project", cascade={"remove", "persist"})
     */
    private $assignments;

    /**
     * @var ArrayCollection $preferedTeams the project prefered teams
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\PreferedTeam", mappedBy="project", cascade={"remove"})
     */
    private $preferedTeams;

    /**
     * @var ArrayCollection $preferedProjects user prefered projects
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\PreferedProject", mappedBy="project", cascade={"remove"})
     */
    private $preferedProjects;

    /**
     * @var ArrayCollection $teamProjects team projects
     *
     * @ORM\ManyToMany(targetEntity="Act\ResourceBundle\Entity\TeamProject", mappedBy="projects", cascade={"remove"})
     */
    private $teamProjects;

    /**
     * @var DateTime $start the project starting date
     * @ORM\Column(name="start", type="date", nullable=true)
     */
    private $start;

    /**
     * @var DateTime $end the project ending date
     * @ORM\Column(name="end", type="date", nullable=true)
     */
    private $end;

    /** utilisé pour stocker des données temporaires **/
    private $tempData;

    /**
     * Constructeur de l'objet Projet
     * Par défaut, un projet est activé.
     */
    public function __construct()
    {
        $this->active = true;
        $this->comments = new ArrayCollection();
        $this->cpts = new ArrayCollection();
        $this->links = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->assignments = new ArrayCollection();
        $this->preferedTeams = new ArrayCollection();
        $this->preferedProjects = new ArrayCollection();
        $this->teamProjects = new ArrayCollection();

        // Generate a random color
        $a = dechex(mt_rand(0,15));
        $b = dechex(mt_rand(0,15));
        $c = dechex(mt_rand(0,15));
        $d = dechex(mt_rand(0,15));
        $e = dechex(mt_rand(0,15));
        $f = dechex(mt_rand(0,15));
        $this->color = '#'. $a . $b . $c . $d . $e . $f;

        $this->typeResearch = false;
        $this->typeInternal = false;
        $this->typeHoliday = false;
        $this->typeSigned = false;
        $this->typePresaleGT70 = false;
        $this->typePresaleLT70 = false;

    }

    /**
     * Renvoi la liste des affectations de ce projet
     * Si une resource est précisée, on renvoi uniquement les affectations de cette resource
     * @param  Resource        $r
     * @return ArrayCollection
     */
    public function getAssignments(Resource $r = null)
    {
        if ($r == null) {
            return $this->assignments;
        } else {
            $result = array();
            foreach ($this->assignments as $a) {
                if ($a->getResource()->getId() == $r->getId()) {
                    $result[] = $a;
                }
            }

            return $result;
        }
    }

    /**
     * Ajoute une affectation à ce projet
     * @param Assignment $a
     */
    public function addAssignment(Assignment $a)
    {
        $this->assignments[] = $a;
    }

    /**
     * Renvoi les tâches de ce projet
     * @return ArrayCollection
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Ajoute une tâche à ce projet
     * @param Task $t
     */
    public function addTask(Task $t)
    {
        $this->tasks[] = $t;
    }

    public function setMetaTasks(array $tasks)
    {
        $this->tasks->clear();
        foreach ($tasks as $t) {
            $this->tasks[] = $t;
            foreach ($t->getCommonTasks() as $ct) {
                $this->tasks[] = $ct;
                foreach ($ct->getSubTasks() as $st) {
                    $this->tasks[] = $st;
                }
            }
        }
    }

    public function getCommontasks()
    {
        $array = array();
        foreach ($this->tasks as $t) {
            if($t instanceof CommonTask)
                $array[] = $t;
        }

        return $array;
    }

    /**
     * Renvoi les commentaires de ce projet
     * @return ArrayCollection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Ajoute un commentaire à ce projet
     * @param Comment $c
     */
    public function addComment(\Act\ResourceBundle\Entity\Comment $c)
    {
        $this->comments[] = $c;
        $c->setProject($this);
    }

    /**
     * Renvoi les chefs de projets techniques du projet
     * @return ArrayCollection
     */
    public function getCpts()
    {
        if($this->cpts == null)
            $this->cpts = new \Doctrine\Common\Collections\ArrayCollection();

        $array = array();
        foreach ($this->cpts as $cpt) {
            if($cpt->getResource() != null)
                $array[] = $cpt;
        }

        return $array;
    }

    /**
     * Renvoi le chef de projet technique pour une équipe donnée
     * @param  Team       $t
     * @return ProjectCpt or null
     */
    public function getCpt(\Act\ResourceBundle\Entity\Team $t)
    {
        if (count($this->cpts) > 0) {
            foreach ($this->cpts as $cpt) {
                if($cpt->getTeam() == $t)

                    return $cpt;
            }
        }

        return null;
    }

    /**
     * Ajoute un chef de projet technique
     * @param ProjectCpt $c
     */
    public function addCpt(\Act\ResourceBundle\Entity\ProjectCpt $c)
    {
        $this->cpts[] = $c;
        $c->setProject($this);
    }

    /**
     * Renvoi les liens de ce projet
     * @return ArrayCollection
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * Ajoute un lien à ce projet
     * @param Link $l
     */
    public function addLink(\Act\ResourceBundle\Entity\Link $l)
    {
        $this->links[] = $l;
        $l->setProject($this);
    }

    /**
     * Renvoi le client de ce projet
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Défini le client de ce projet
     * @param Client $client
     */
    public function setClient(\Act\ResourceBundle\Entity\Client $client = null)
    {
        $this->client = $client;
    }

    /**
     * Renvoi le chef de projet fonctionnel
     * @return Resource
     */
    public function getCpf()
    {
        return $this->cpf;
    }

    /**
     * Défini le chef de projet fonctionnel
     * @param Resource $cpf
     */
    public function setCpf(\Act\ResourceBundle\Entity\Resource $cpf = null)
    {
        $this->cpf = $cpf;
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
     * Set active
     *
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * Is active
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Affichage en tant que chaîne de caractère
     * @return String
     */
    public function __toString()
    {
        return $this->name.' ('.$this->nameShort.')';
    }

    /**
     * Renvoi la date de début du projet
     * @return DateTime
     */
    public function getStart()
    {
        if ($this->start != null) {
            return clone $this->start;
        } else {
            return null;
        }
    }

    /**
     * Set Start
     *
     * @param DateTime $date
     */
    public function setStart(\DateTime $date)
    {
        $this->start = $date;
    }

    /**
     * Renvoi la date de fin du projet
     * @return DateTime
     */
    public function getEnd()
    {
        if ($this->end != null) {
            return clone $this->end;
        } else {
            return null;
        }
    }

    /**
     * Set End
     *
     * @param DateTime $date
     */
    public function setEnd(\DateTime $date)
    {
        $this->end = $date;
    }

    /**
     * Renvoi la durée totale du projet en jours
     * @return Array['nbDays','nbWorkingDays']
     */
    public function getDuration()
    {
        $start = $this->getStart();
        $end = $this->getEnd();
        $interval = \DateInterval::createFromDateString('1 day');
        $result = array();
        $result['nbDays'] = 0;
        $result['nbWorkingDays'] = 0;

        while ($start < $end) {
            $start->add($interval);
            if ($start->format('N') < 6) {
                $result['nbWorkingDays']++;
                $result['nbDays']++;
            } else {
                $result['nbDays']++;
            }
        }

        return $result;
    }

    /**
     * Renvoi le total de workload vendu de ce projet
     * NB: On additionne le workload vendu des métatâches uniquement, car les tâches et sous-tâches reflètent juste ce chiffre
     * @return float
     */
    public function getTotalSold(Team $team = null)
    {
        $total = 0;
        foreach ($this->tasks as $t) {
            if ($t instanceof MetaTask) {
                if ($team == null) {
                    $total += $t->getWorkloadSold();
                } else {
                    // On teste si la tâche appartient à l'équipe
                    if ($t->belongsTo($team)) {
                        $total += $t->getWorkloadSold();
                    }
                }
            }
        }

        return $total;
    }

    /**
     * Renvoi le total de workload affecté dans ce projet
     * @return float
     */
    public function getTotalAssigned()
    {
        $total = 0;
        foreach ($this->assignments as $a) {
            $total += $a->getWorkload();
        }

        return $total;
    }

    /**
     * Renvoi le nombre de resources concernées par le projet
     * NB: le nombre de resource ayant au moins une affectation ou tâche dans ce projet
     * @return int
     */
    public function getNbResourcesInvolved()
    {
        $resources = array();

        foreach ($this->assignments as $a) {
            if($a->getResource() && !in_array($a->getResource()->getId(), $resources))
                $resources[] = $a->getResource()->getId();
        }

        return count($resources);
    }

    /**
     * Renvoi le nombre d'équipes concernées par le projet
     * NB: le nombre d'équipe ayant au moins une affectation ou tâche dans ce projet
     * @return int
     */
    public function getNbTeamsInvolved()
    {
        $teams = array();

        foreach ($this->assignments as $a) {
            if($a->getResource()->getTeam() && !in_array($a->getResource()->getTeam()->getId(), $teams))
                $teams[] = $a->getResource()->getTeam()->getId();
        }

        foreach ($this->tasks as $t) {
            foreach ($t->getTeams() as $te) {
                if (!in_array($te->getId(), $teams)) {
                    $teams[] = $te->getId();
                }
            }

            foreach ($t->getTeamProfiles() as $tp) {
                if (!in_array($tp->getTeam()->getId(), $teams)) {
                    $teams[] = $tp->getTeam()->getId();
                }
            }
        }

        return count($teams);
    }

    /**
     * Supprime un commentaire
     * @param Comment $comment
     */
    public function removeComment(\Act\ResourceBundle\Entity\Comment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Supprime un chef de projet technique
     * @param ProjectCpt $cpt
     */
    public function removeCpt(\Act\ResourceBundle\Entity\ProjectCpt $cpt)
    {
        $this->cpts->removeElement($cpt);
    }

    /**
     * Supprime un lien
     * @param Link $link
     */
    public function removeLink(\Act\ResourceBundle\Entity\Link $link)
    {
        $this->links->removeElement($link);
    }

    /**
     * Supprime une tâche
     * @param Task $task
     */
    public function removeTask(\Act\ResourceBundle\Entity\Task $task)
    {
        $this->tasks->removeElement($task);
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
     * Set color
     *
     * @param  string  $color
     * @return Project
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
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
     * Génère un code à partir du nom
     */
    public function generateNameShort()
    {
        if (strlen($this->name) > 3) {
            $this->nameShort = strtoupper(substr($this->name, 0, 1).substr($this->name, 1, 1).substr($this->name, -1));
        } else {
            $this->nameShort = strtoupper($this->name);
        }
    }

    /**
     * Renvoi uniquement les métatâches directement assignées à l'équipe demandée ou à un profil de l'équipe
     * @param  \Act\ResourceBundle\Entity\Team $team
     * @return array
     */
    public function getMetaTasksInvolving(Team $team = null)
    {
        $array = array();
        foreach ($this->tasks as $t) {
            // Si la tâche appartient à l'équipe, on récupère sa métatâche
            if ($t->belongsTo($team)) {
                if(!isset($array[$t->getMetatask()->getId()]))
                    $array[$t->getMetatask()->getId()] = $t->getMetatask();
            }
        }

        return $array;
    }

    /**
     * Renvoi les métatâches du projet
     * @return array
     */
    public function getMetaTasks()
    {
        $array = array();
        foreach ($this->tasks as $task) {
            if ($task instanceof MetaTask) {
                $array[] = $task;
            }
        }

        return $array;
    }

    /**
     * Renvoi le nombre total de tâches du projet impliquant l'équipe
     * @param  \Act\ResourceBundle\Entity\Team $team
     * @return int
     */
    public function getNbTasksInvolving(Team $team = null, $includeSubTasks = true)
    {
        $nb = 0;
        foreach ($this->getMetaTasksInvolving($team) as $meta) {
            $nb++;
            foreach ($meta->getCommonTasksInvolving($team) as $common) {
                $nb++;
                if ($includeSubTasks) {
                    $nb += count($common->getSubTasksInvolving($team));
                }
            }
        }

        return $nb;
    }

    /**
     * Renvoi un tableau des tâches du projet organisées en lignes pour affichage d'un tableau
     * @return array
     */
    public function getTasksAsRows(\DateTime $start, \DateTime $end, Team $team = null)
    {
        // On regarde si on ne l'a pas déjà calculée
        if (isset($this->tempData[$start->format('dmY')][$end->format('dmY')][($team == null ? 'noteam' : $team->getId())]['tasks'])) {
            return $this->tempData[$start->format('dmY')][$end->format('dmY')][($team == null ? 'noteam' : $team->getId())]['tasks'];
        } // Sinon on fait le calcul, et on le met en cache dans la variable

        $finalRows = array();

        $task = null; $i = 0; $metaTasksRows = array(); $continue = true; $nbNull = 0;
        // On récupère les métatâches classées en lignes
        while ($continue) {
            $nextTask = $this->findNextMetaTask($start, $end, $task, $team);

            if ($nextTask != null) {
                $metaTasksRows[$i][] = $nextTask;
                $task = $nextTask;
                $task->setShown(true);
            } else {
                $i++;
                if ($task == null) {
                    $nbNull++;
                }
                $task = null;
                if ($nbNull > 2) {
                    $continue = false;
                }
            }
        }

        // On va générer un tableau des lignes pour la génération du tableau des tâches avec TWIG
        // Pour chaque rangée de métatâche on récupère les tâches classées en lignes et on fusionne les indices communs
        // pour avoir les tâches sur une seule ligne si possible
        foreach ($metaTasksRows as $metaRowIndex => $metaTaskRow) {
            // On ajoute déjà la rangée de métatâches au tableau des rangées finales
            $finalRows[] = $metaTaskRow;

            // Puis on cherche les rangées des tâches fusionnées qui vont suivre
            $commonTasksRowsFusion = array();
            foreach ($metaTaskRow as $metaTask) {
                $commonTasksRows = $metaTask->getCommonTasksOrdered($start, $end, $team);
                foreach ($commonTasksRows as $commonRowIndex => $commonTaskRow) {
                    foreach ($commonTaskRow as $commonTask) {
                        $commonTasksRowsFusion[$commonRowIndex][] = $commonTask;
                    }
                }
            }

            // Pour chaque rangée de tâches on récupère les sous-tâches classées en lignes et on fusionne les indices communs
            // pour avoir les sous-tâches sur une seule ligne si possible
            foreach ($commonTasksRowsFusion as $commonTasksRow) {
                $finalRows[] = $commonTasksRow;

                $subTasksRowsFusion = array();
                foreach ($commonTasksRow as $commonTask) {
                    $subTasksRows = $commonTask->getSubTasksOrdered($start, $end, $team);
                    foreach ($subTasksRows as $subRowIndex => $subTaskRow) {
                        foreach ($subTaskRow as $subTask) {
                            $subTasksRowsFusion[$subRowIndex][] = $subTask;
                        }
                    }
                }

                foreach ($subTasksRowsFusion as $subTasksRow) {
                    $finalRows[] = $subTasksRow;
                }
            }
        }

        // On reset pour les autres équipes
        $totalShown = 0;
        foreach ($this->tasks as $t) {
            if ($t->isShown()) {
                $totalShown++;
                $t->setShown(false);
            }
        }

        // On met en cache
        $this->tempData[$start->format('dmY')][$end->format('dmY')][($team == null ? 'noteam' : $team->getId())]['tasks'] = $finalRows;
        $this->tempData[$start->format('dmY')][$end->format('dmY')][($team == null ? 'noteam' : $team->getId())]['total'] = $totalShown;

        // On renvoi tout le tableau des rangées
        return $finalRows;
    }

    /**
     * Renvoi le nombre de tâches qui sont affichées sur le planning projet pour une équipe et des dates données
     * @return int
     */
    public function getTotalTasksShown(\DateTime $start, \DateTime $end, Team $team = null)
    {
        // On regarde si on ne l'a pas déjà calculée
        if (isset($this->tempData[$start->format('dmY')][$end->format('dmY')][($team == null ? 'noteam' : $team->getId())]['total'])) {
            return $this->tempData[$start->format('dmY')][$end->format('dmY')][($team == null ? 'noteam' : $team->getId())]['total'];
        } else {
            // Sinon on fait le calcul, et on le met en cache dans la variable
            $this->getTasksAsRows($start, $end, $team);
        }
    }

    /**
     * Recherche la métatâche qui suit au plus près la métatâche donnée
     * @return MetaTask/null
     */
    private function findNextMetaTask($start, $end, MetaTask $task = null, Team $team = null)
    {
        if ($task == null) {
            // On cherche la première tâche
            $minTask = null;
            foreach ($this->tasks as $t) {
                if ($t instanceof MetaTask) {
                    if (!$t->isShown() && $t->getStart() <= $end && $t->getEnd() >= $start) {
                        if ($t->belongsTo($team) || $t->countChildsInvolving($team) > 0) {
                            // Si aucun minimum, on prend la première tâche non traitée trouvée
                            if ($minTask == null) {
                                $minTask = $t;
                                continue;
                            }

                            if ($minTask != null && $t->getStart() < $minTask->getStart()) {
                                // Sinon il faut qu'elle commence plus tôt que le mimimum courant
                                $minTask = $t;
                            }
                        }
                    }
                }
            }

            return $minTask;
        } else {
            // On cherche la tâche la plus proche de la tâche donnée
            $minTask = null; $minDate = null;
            foreach ($this->tasks as $t) {
                if ($t instanceof MetaTask) {
                    if (!$t->isShown() && $t->getStart() <= $end && $t->getEnd() >= $start) {
                        if ($t->belongsTo($team) || $t->countChildsInvolving($team) > 0) {
                            if ($t->getStart() > $task->getEnd()) {
                                // La tâche est eligible, on vérifie si elle est minimale
                                if ($minDate == null || ($t->getStart() < $minDate)) {
                                    $minTask = $t;
                                    $minDate = $t->getStart();
                                }
                            }
                        }
                    }
                }
            }

            return $minTask;
        }
    }

    /**
     * Récupère le nombre total de jours affectés à cette équipe
     * @param \Act\ResourceBundle\Entity\Team $t
     */
    public function getTotalAffected(Team $t)
    {
        $result = 0;
        foreach ($this->assignments as $a) {
            if ($a->getResource()->getTeam()->getId() == $t->getId()) {
                $result += $a->getWorkload();
            }
        }

        return $result;
    }

    /**
     * Récupère le nombre total de jours non vendus pour cette équipe
     * @param \Act\ResourceBundle\Entity\Team $t
     */
    public function getTotalUnsold(Team $t)
    {
        $result = 0;
        foreach ($this->assignments as $a) {
            $unsold = $a->getUnsold();
            if ($unsold > 0) {
                if ($a->getResource()->getTeam()->getId() == $t->getId()) {
                    $result += $unsold;
                }
            }
        }

        return $result;
    }

    public function getPreferedTeams()
    {
        return $this->preferedTeams;
    }

    public function addPreferedTeam(\Act\ResourceBundle\Entity\PreferedTeam $pt)
    {
        $this->preferedTeams[] = $pt;
    }

    public function removePreferedTeam(\Act\ResourceBundle\Entity\PreferedTeam $pt)
    {
        $this->preferedTeams->removeElement($pt);
    }

    public function getPreferedProjects()
    {
        return $this->preferedProjects;
    }

    public function addPreferedProjects(\Act\ResourceBundle\Entity\PreferedProject $pp)
    {
        $this->preferedProjects[] = $pp;
    }

    public function removePreferedProjects(\Act\ResourceBundle\Entity\PreferedProject $pp)
    {
        $this->preferedProjects->removeElement($pp);
    }

    public function getTeamProjects()
    {
        return $this->teamProjects;
    }

    public function addTeamProject(\Act\ResourceBundle\Entity\TeamProject $tp)
    {
        $this->teamProjects[] = $tp;
    }

    public function removeTeamProject(\Act\ResourceBundle\Entity\TeamProject $tp)
    {
        $this->teamProjects->removeElement($tp);
    }

    public function isTypePresaleGT70()
    {
        return $this->typePresaleGT70;
    }

    public function isProjectSignee()
    {
        return $this->project_signee;
    }

    public function isTypePresaleLT70()
    {
        return $this->typePresaleLT70;
    }

    public function isTypeSigned()
    {
        return $this->typeSigned;
    }

    public function isTypeHoliday()
    {
        return $this->typeHoliday;
    }

    public function isTypeInternal()
    {
        return $this->typeInternal;
    }

    public function isTypeResearch()
    {
        return $this->typeResearch;
    }

    public function setTypePresaleGT70($value)
    {
        $this->typePresaleGT70 = $value;
    }

    public function setProjectSignee($value)
    {
        $this->project_signee = $value;
    }

    public function setTypePresaleLT70($value)
    {
        $this->typePresaleLT70 = $value;
    }

    public function setTypeSigned($value)
    {
        $this->typeSigned = $value;
    }

    public function setTypeHoliday($value)
    {
        $this->typeHoliday = $value;
    }

    public function setTypeInternal($value)
    {
        $this->typeInternal = $value;
    }

    public function setTypeResearch($value)
    {
        $this->typeResearch = $value;
    }
}
