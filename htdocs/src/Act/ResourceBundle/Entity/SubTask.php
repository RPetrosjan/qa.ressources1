<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SubTask entity code
 *
 * @ORM\Table(name="subtask")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class SubTask extends Task
{
    /**
     * @var ArrayCollection $commontask La tache parente de cette sous-tache
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\CommonTask", inversedBy="subtasks")
     */
    protected $commontask;

    /**
     * @var ArrayCollection $assignments Les affectations liées à cette sous-tâche
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\Assignment", mappedBy="subtask", cascade={"persist"})
     */
    protected $assignments;

    public function __construct()
    {
        parent::__construct();
        $this->assignments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Renvoi la somme du workload affecté à cette tâche
     * @return float
     */
    public function getSumWorkloadAssigned()
    {
        $total = 0;
        foreach ($this->assignments as $assignment) {
            $total += $assignment->getWorkload();
        }

        return $total;
    }

    /**
     * Renvoi le total de workload affecté à cette tâche
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
     * Renvoi le nombre de ressources concernées par cette tâche
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
     * Renvoi le nombre d'équipes concernées par cette tâche
     * @return int
     */
    public function getNbTeamsInvolved()
    {
        $teams = array();

        foreach ($this->assignments as $a) {
            if($a->getResource()->getTeam() && !in_array($a->getResource()->getTeam()->getId(), $teams))
                $teams[] = $a->getResource()->getTeam()->getId();
        }

        return count($teams);
    }

    /**
     * Set commontask
     *
     * @param  Act\ResourceBundle\Entity\CommonTask $commontask
     * @return SubTask
     */
    public function setCommontask(\Act\ResourceBundle\Entity\CommonTask $commontask)
    {
        $this->commontask = $commontask;

        return $this;
    }

    /**
     * Get commontask
     *
     * @return Act\ResourceBundle\Entity\CommonTask
     */
    public function getCommontask()
    {
        return $this->commontask;
    }

    /**
     * Add assignments
     *
     * @param  Act\ResourceBundle\Entity\Assignment $assignments
     * @return SubTask
     */
    public function addAssignment(\Act\ResourceBundle\Entity\Assignment $assignments)
    {
        $this->assignments[] = $assignments;
        $assignments->setSubtask($this);

        return $this;
    }

    /**
     * Remove assignments
     *
     * @param Act\ResourceBundle\Entity\Assignment $assignments
     */
    public function removeAssignment(\Act\ResourceBundle\Entity\Assignment $assignments)
    {
        $this->assignments->removeElement($assignments);
    }

    /**
     * Get assignments
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getAssignments()
    {
        return $this->assignments;
    }

    public function countChildsInvolving(Team $team = null)
    {
        return 0;
    }

    public function getMetaTask()
    {
        return $this->commontask->getMetaTask();
    }

    /**
     * Attention : ne pas changer, à des répercussion sur certains traitements (affichage tâches planning)
     */
    public function getType()
    {
        return 'sous-tâche';
    }

    public function getNameForExcel()
    {
        return '      '.$this->name;
    }

    public function getCSSClass()
    {
        return 'subtask';
    }

    public function setStart(\DateTime $start, $force = false)
    {
        $lastStart = null;
        if($this->start != null)
            $lastStart = clone $this->start;

        parent::setStart($start);

        if ($force || $lastStart != null) {
            // Si on augmente la durée, on doit répercuter les modifs
            if ($force || $lastStart > $this->start) {
                if ($this->commontask != null && $this->commontask->getStart() > $this->start) {
                    $this->commontask->setStart($this->start);
                }
            }
        }
    }

    public function setEnd(\DateTime $end, $force = false)
    {
        $lastEnd = null;
        if($this->end != null)
            $lastEnd = clone $this->end;

        parent::setEnd($end);

        if ($force || $lastEnd != null) {
            // Si on augmente la durée, on doit répercuter les modifs
            if ($force || $lastEnd < $this->end) {
                if ($this->commontask != null && $this->commontask->getEnd() < $this->end) {
                    $this->commontask->setEnd($this->end);
                }
            }
        }
    }

    public function getParent()
    {
        return $this->commontask;
    }

    public function getTotalChildrenWorkloadSold()
    {
        return $this->workload_sold;
    }

    /**
     * Avant la suppression d'une sous-tâche, on retire les affectations
     * @ORM\PreRemove
     */
    public function beforeRemove()
    {
        foreach ($this->assignments as $a) {
            $a->setSubtask(null);
            $this->removeAssignment($a);
        }
    }

    /**
     * Returns all resources assigned to this task
     * @return array
     */
    public function getAllResourcesAssigned()
    {
        $result = array();

        foreach ($this->assignments as $a) {
            $result[$a->getResource()->getId()] = $a->getResource();
        }

        return $result;
    }
}
