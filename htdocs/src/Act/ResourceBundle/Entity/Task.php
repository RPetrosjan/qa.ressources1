<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Task Entity
 * A task is attached to a project, and can have one or more teams and profiles attached.
 * There are 3 kind of tasks : Meta tasks, common tasks and sub tasks.
 * Some of them (common and sub) can have assignments attached.
 *
 * @ORM\Table(name="task")
 * @ORM\Entity(repositoryClass="Act\ResourceBundle\Entity\TaskRepository")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string", length=6)
 * @ORM\DiscriminatorMap({"meta" = "MetaTask", "common" = "CommonTask", "sub" = "SubTask"})
 */
abstract class Task
{
    /**
     * @var integer $id the task id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $name the task name
     *
     * @Assert\NotBlank()
     * @ORM\Column(name="name", type="string", length=128)
     */
    protected $name;

    /**
     * @var \DateTime $start the task start date
     *
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     * @ORM\Column(name="start", type="date")
     */
    protected $start;

    /**
     * @var \DateTime $end the task end date
     *
     * @Assert\NotBlank()
     * @Assert\Type("\DateTime")
     * @ORM\Column(name="end", type="date")
     */
    protected $end;

    /**
     * @var float $workload_sold the task sold workload
     *
     * @Assert\Type("numeric")
     * @Assert\GreaterThanOrEqual(value = 0)
     * @ORM\Column(name="workload_sold", type="decimal", scale=2, nullable=true)
     */
    protected $workload_sold;

    /**
     * @var Team $teams the teams associated to the task
     *
     * @ORM\ManyToMany(targetEntity="Act\ResourceBundle\Entity\Team")
     */
    protected $teams;

    /**
     * @var TeamProfile $teamprofiles the profiles associated to the task
     *
     * @ORM\ManyToMany(targetEntity="Act\ResourceBundle\Entity\TeamProfile")
     */
    protected $teamprofiles;

    /**
     * @var Project $project the project in which the task is located
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Project", inversedBy="tasks")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    protected $project;

    /**
     * @var Boolean $shown Used by the task ordering algorithm
     * @todo find a better way to do this ?
     */
    protected $shown = false;

    /**
     * Task constructor
     */
    public function __construct()
    {
        $this->teams = new \Doctrine\Common\Collections\ArrayCollection();
        $this->teamprofiles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Returns this task teams
     *
     * @return Team
     */
    public function getTeams()
    {
        return $this->teams;
    }

    /**
     * Return this task project
     *
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Define this task project
     * Bidirectional setter
     *
     * @param Project $project
     */
    public function setProject(Project $project)
    {
        $this->project = $project;
        $project->addTask($this);
    }

    /**
     * Get the task id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the task id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set the task name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = trim($name);
    }

    /**
     * Get the task name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the task starting date
     *
     * @param \DateTime $start
     */
    public function setStart(\DateTime $start)
    {
        $this->start = clone $start;
        $this->start->setTime(0, 0, 0);

        // Check that the ending date is after the starting date
        if ($this->end < $this->start) {
            $this->end = clone $this->start;
            $this->end->setTime(23, 59, 59);
        }
    }

    /**
     * Get the task starting date
     *
     * @return \DateTime
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
     * Set the task ending date
     *
     * @param \DateTime $end
     */
    public function setEnd(\DateTime $end)
    {
        $this->end = clone $end;
        $this->end->setTime(23, 59, 59);

        // Check that the starting date is before the ending date
        if ($this->start > $this->end) {
            $this->start = clone $this->end;
            $this->start->setTime(0, 0, 0);
        }
    }

    /**
     * Get the task ending date
     *
     * @return \DateTime
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
     * Set the task workload sold
     * This workload can not be negative
     *
     * @param float $workloadSold
     */
    public function setWorkloadSold($workload)
    {
        if ($workload == null) {
            $this->workload_sold = null;
        } else {
            if ($workload < 0) {
                $this->workload_sold = 0;
            } else {
                $this->workload_sold = (float) $workload;
            }
        }
    }

    /**
     * Get the task workload sold
     *
     * @return float
     */
    public function getWorkloadSold()
    {
        return (float) $this->workload_sold;
    }

    /**
     * Display the task as a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Compute the unsold workload of the task
     *
     * @return float
     */
    public function getUnsold()
    {
        $unsold = ($this->getSumWorkloadAssigned() - $this->getWorkloadSold());

        return ($unsold > 0 ? $unsold : 0);
    }

    /**
     * Shift the task X working days in the past or future
     *
     * @param int $nbdays        the number X of days to shift the assignment
     * @param int $timeDirection 0 = shift in the past, 1 = shift in the future
     *
     * @return Task
     */
    public function shift($nbdays, $timeDirection)
    {
        $interval = \DateInterval::createFromDateString('1 day');
        $start = clone $this->start; // Because Doctrine doesn't detect change otherwise
        $end = clone $this->end;     // Because Doctrine doesn't detect change otherwise
        $i = 0;
        $j = 0;

        if ($timeDirection == 0) {
            // In the past
            while ($i < $nbdays) {
                // Do not count weekends days
                $start->sub($interval);
                if ($start->format('N') < 6) {
                    $i++;
                }
            }

            while ($j < $nbdays) {
                // Do not count weekends days
                $end->sub($interval);
                if ($end->format('N') < 6) {
                    $j++;
                }
            }
        } else {
            // In the future
            while ($i < $nbdays) {
                // Do not count weekends days
                $start->add($interval);
                if ($start->format('N') < 6) {
                    $i++;
                }
            }

            while ($j < $nbdays) {
                // Do not count weekends days
                $end->add($interval);
                if ($end->format('N') < 6) {
                    $j++;
                }
            }
        }

        $this->start = $start;
        $this->end = $end;

        return $this;
    }

    /**
     * Returns the number of profiles and teams associated to the task
     *
     * @return int
     */
    public function getNbTeamsAndProfiles()
    {
        return count($this->teams) + count($this->teamprofiles);
    }

    /**
     * Returns the total duration of the task, in days
     * The result is an array with two keys : nbDays and nbWorkingDays
     *
     * @return array
     */
    public function getDuration()
    {
        $start = $this->getStart();
        $end = $this->getEnd();
        $interval = \DateInterval::createFromDateString('1 day');
        $result = array();
        $result['nbDays'] = 0;
        $result['nbWorkingDays'] = 0;

        while ($start <= $end) {
            if ($start->format('N') < 6) {
                $result['nbWorkingDays']++;
                $result['nbDays']++;
            } else {
                $result['nbDays']++;
            }
            $start->add($interval);
        }

        return $result;
    }


    /**
     * Add a team to the task
     *
     * @param Team $team
     */
    public function addTeam(Team $team)
    {
        $this->teams[] = $team;
    }

    /**
     * Remove a team from the task
     *
     * @param Team $team
     */
    public function removeTeam(Team $team)
    {
        $this->teams->removeElement($team);
    }

    /**
     * Add a profile to the task
     *
     * @param TeamProfile $profile
     */
    public function addTeamprofile(TeamProfile $profile)
    {
        $this->teamprofiles[] = $profile;
    }

    /**
     * Remove a profile from the task
     *
     * @param TeamProfile $profile
     */
    public function removeTeamprofile(TeamProfile $profile)
    {
        $this->teamprofiles->removeElement($profile);
    }

    /**
     * Get the task profiles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTeamprofiles()
    {
        return $this->teamprofiles;
    }

    /**
     * Determines if the task belongs to the given team
     * If the given team is null, then checks if the task belongs to no teams at all
     *
     * @param Team $t
     *
     * @return boolean
     */
    public function belongsTo(Team $t = null)
    {
        if ($t == null) {
            // We want to know if the task belongs to no teams
            if (count($this->teams) == 0 && count($this->teamprofiles) == 0) {
                return true;
            } else {
                return false;
            }
        }

        // Check if the task has the team linked to it
        foreach ($this->teams as $team) {
            if ($team->getId() == $t->getId()) {
                return true;
            }
        }

        // Check if the task has a profile of the given team linked to it
        foreach ($this->teamprofiles as $profile) {
            if ($profile->getTeam()->getId() == $t->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if the task is linked to the given profile
     *
     * @param TeamProfile $tp
     *
     * @return boolean
     */
    public function hasTeamProfile(TeamProfile $tp)
    {
        foreach ($this->teamprofiles as $profile) {
            if ($profile->getId() == $tp->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if the task is linked to the given team
     *
     * @param Team $t
     *
     * @return boolean
     */
    public function hasTeam(Team $t)
    {
        foreach ($this->teams as $team) {
            if ($team->getId() == $t->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Formate les équipes et profils pour export vers Excel
     * @todo transfer this code to a service
     * @return String
     */
    public function formatTeamsAndProfilesForExcel()
    {
        $string = '';
        foreach ($this->teams as $t) {
            $string .= $t->getName().';';
        }
        foreach ($this->teamprofiles as $tp) {
            $string .= $tp->getName().';';
        }

        return $string;
    }

    /**
     * Ces deux fonctions sont utilisés par l'algo de tri des tâches pour l'affichage planning
     * A ne pas utiliser autrement car inutile
     * @todo find an other way to do this, a proper way
     * @return Bool
     */
    public function isShown()
    {
        return $this->shown;
    }

    public function setShown($bool)
    {
        $this->shown = $bool;
    }

    /**
     * Hydrate les données de cette tâche en copiant celles d'une autre tâche
     * @param \Act\ResourceBundle\Entity\Task $t
     */
    public function hydrateWithOtherTask(Task $t)
    {
        $this->setName($t->getName());
        $this->setStart($t->getStart(), true);
        $this->setEnd($t->getEnd(), true);
        $this->setProject($t->getProject());
        $this->setWorkloadSold($t->getWorkloadSold());
        foreach ($t->getTeams() as $team) {
            $this->addTeam($team);
        }
        foreach ($t->getTeamprofiles() as $tp) {
            $this->addTeamprofile($tp);
        }
    }

    /**
     * S'assure que les tâches liées ont des dates valides et les met à jour le cas contraire
     */
    public function ensureLinkedTasksDates()
    {
        $this->setStart($this->getStart(), true);
        $this->setEnd($this->getEnd(), true);
    }

    /**
     * Renvoi toutes les équipes associées à cette tâche
     * @return Array
     */
    public function getAssociatedTeams()
    {
        $teams = array();
        foreach ($this->teams as $t) {
            $teams[$t->getId()] = $t;
        }
        foreach ($this->teamprofiles as $tp) {
            $teams[$tp->getTeam()->getId()] = $tp->getTeam();
        }

        return $teams;
    }

    /**
     * Remove all teams and profiles of this task
     */
    public function clearTeamsAndProfiles()
    {
        $this->teamprofiles->clear();
        $this->teams->clear();
    }

    /**
     * Formate le nom pour export vers Excel
     */
    abstract public function getNameForExcel();

    /**
     * Renvoi le nombre de tâches enfants de cette tâche dans lesquelles l'équipe est impliquée
     */
    abstract public function countChildsInvolving(Team $team = null);

    /**
     * Récupère la métatâche parente de la tâche/soustâche
     */
    abstract public function getMetaTask();

    /**
     * Renvoi le type de la tâche pour affichage
     */
    abstract public function getType();

    /**
     * Renvoi la class CSS associé a ce type de tâche
     */
    abstract public function getCSSClass();

    /**
     * Renvoi la tâche parente de cette tâche
     */
    abstract public function getParent();

    /**
     * Renvoi le workload total pour les tâches filles
     */
    abstract public function getTotalChildrenWorkloadSold();

    /**
     * Returns the total workload assigned to the task
     *
     * @return float
     */
    abstract public function getTotalAssigned();

    /**
     * Returns the number of resources implied in the task
     * @return integer
     */
    abstract public function getNbResourcesInvolved();

    /**
     * Returns the number of teams involved in the task
     *
     * @return int
     */
    abstract public function getNbTeamsInvolved();

    /**
     * Return the workload sum assigned to the task
     *
     * @return float
     */
    abstract public function getSumWorkloadAssigned();

    /**
     * Return all resources assigned to this task
     *
     * @return array
     */
    abstract public function getAllResourcesAssigned();
}
