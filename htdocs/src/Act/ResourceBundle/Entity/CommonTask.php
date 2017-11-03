<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CommonTask Entity
 * A commontask is a child of a metatask and can be added to a project.
 *
 * @ORM\Table(name="commontask")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class CommonTask extends Task
{
    /**
     * @var ArrayCollection $metatask the parent task
     *
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\MetaTask", inversedBy="commontasks")
     */
    protected $metatask;

    /**
     * @var ArrayCollection $subtasks subtasks of this task
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\SubTask", mappedBy="commontask", cascade={"persist", "remove"})
     */
    protected $subtasks;

    /**
     * @var ArrayCollection $assignments assignments of this task
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\Assignment", mappedBy="commontask", cascade={"persist"})
     */
    protected $assignments;

    /**
     * Construct a new CommonTask object
     */
    public function __construct()
    {
        parent::__construct();
        $this->subtasks = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set metatask
     *
     * @param  Act\ResourceBundle\Entity\MetaTask $metatask
     * @return CommonTask
     */
    public function setMetatask(\Act\ResourceBundle\Entity\MetaTask $metatask)
    {
        $this->metatask = $metatask;

        return $this;
    }

    /**
     * Get metatask
     *
     * @return Act\ResourceBundle\Entity\MetaTask
     */
    public function getMetatask()
    {
        return $this->metatask;
    }

    /**
     * Add subtasks
     *
     * @param  Act\ResourceBundle\Entity\SubTask $subtasks
     * @return CommonTask
     */
    public function addSubtask(\Act\ResourceBundle\Entity\SubTask $subtasks)
    {
        $this->subtasks[] = $subtasks;
        $subtasks->setCommontask($this);

        return $this;
    }

    /**
     * Remove subtasks
     *
     * @param Act\ResourceBundle\Entity\SubTask $subtasks
     */
    public function removeSubtask(\Act\ResourceBundle\Entity\SubTask $subtasks)
    {
        $this->subtasks->removeElement($subtasks);
    }

    /**
     * Get subtasks
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getSubtasks()
    {
        return $this->subtasks;
    }

    /**
     * Add assignments
     *
     * @param  Act\ResourceBundle\Entity\Assignment $assignments
     * @return CommonTask
     */
    public function addAssignment(\Act\ResourceBundle\Entity\Assignment $assignments)
    {
        $this->assignments[] = $assignments;
        $assignments->setCommontask($this);

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
        $nb = 0;
        foreach ($this->subtasks as $t) {
            if ($t->belongsTo($team)) {
                $nb++;
            }
        }

        return $nb;
    }

    public function getSubTasksInvolving(Team $team = null)
    {
        $array = array();
        foreach ($this->subtasks as $st) {
            if ($st->belongsTo($team)) {
                $array[] = $st;
            }
        }

        return $array;
    }

    public function hasSubTasksInvolving(Team $team = null)
    {
        foreach ($this->subtasks as $st) {
            if ($st->belongsTo($team)) {
                return true;
            }
        }

        return false;
    }

    public function getType()
    {
        return 'tâche';
    }

    /**
     * Renvoi un tableau contenant les sous-tâches appartenant à cette tâche,
     * de manière ordonnée si plusieurs sous-tâches peuvent se suivre
     * @return array
     */
    public function getSubTasksOrdered(\DateTime $start, \DateTime $end, Team $team = null)
    {
        $task = null; $i = 0; $array = array(); $continue = true; $nbNull = 0;
        while ($continue) {
            $nextTask = $this->findNextTask($start, $end, $task, $team);

            if ($nextTask != null) {
                $array[$i][] = $nextTask;
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

        return $array;
    }

    /**
     * Recherche la sous-tâche qui suit au plus près la sous-tâche donnée
     * @return SubTask/null
     */
    private function findNextTask(\DateTime $start, \DateTime $end, SubTask $task = null, Team $team = null)
    {
        if ($task == null) {
            // On cherche la première tâche
            $minTask = null;
            foreach ($this->subtasks as $t) {
                if (!$t->isShown() && $t->getStart() <= $end && $t->getEnd() >= $start) {
                    if ($t->belongsTo($team)) {
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

            return $minTask;
        } else {
            // On cherche la tâche la plus proche de la tâche donnée
            $minTask = null; $minDate = null;
            foreach ($this->subtasks as $t) {
                if (!$t->isShown() && $t->getStart() <= $end && $t->getEnd() >= $start) {
                    if ($t->belongsTo($team)) {
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

            return $minTask;
        }
    }

    public function getNameForExcel()
    {
        return '   '.$this->name;
    }

    public function getCSSClass()
    {
        return 'commontask';
    }

    /**
     * Set the task starting date
     * @param \DateTime $start
     * @param bool      $force force la vérification et la mise à jour des dates même si aucun changements
     */
    public function setStart(\DateTime $start, $force = false)
    {
        $lastStart = null;
        if($this->start != null)
            $lastStart = clone $this->start;

        parent::setStart($start);

        if ($force || $lastStart != null) {
            // Si on réduit la durée, on doit répercuter les modifs
            if ($force || $lastStart < $this->start) {
                foreach ($this->subtasks as $stask) {
                    if ($stask->getStart() < $this->start) {
                        $stask->setStart($this->start);
                    }
                }
            }

            // Si on augmente la durée, on doit répercuter les modifs
            if ($force || $lastStart > $this->start) {
                if ($this->metatask != null && $this->metatask->getStart() > $this->start) {
                    $this->metatask->setStart($this->start);
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
            // Si on réduit la durée, on doit répercuter les modifs
            if ($force || $lastEnd > $this->end) {
                foreach ($this->subtasks as $stask) {
                    if ($stask->getEnd() > $this->end) {
                        $stask->setEnd($this->end);
                    }
                }
            }

            // Si on augmente la durée, on doit répercuter les modifs
            if ($force || $lastEnd < $this->end) {
                if ($this->metatask != null && $this->metatask->getEnd() < $this->end) {
                    $this->metatask->setEnd($this->end);
                }
            }
        }
    }

    public function getParent()
    {
        return $this->metatask;
    }

    public function getTotalChildrenWorkloadSold()
    {
        $result = 0;
        if (count($this->subtasks) == 0) {
            $result = $this->workload_sold;
        } else {
            foreach ($this->subtasks as $s) {
                $result += $s->getTotalChildrenWorkloadSold();
            }
        }

        return $result;
    }

    /**
     * Avant la suppression d'une tâche, on retire les affectations
     * @ORM\PreRemove
     */
    public function beforeRemove()
    {
        foreach ($this->assignments as $a) {
            $a->setSubtask(null);
            $a->setCommontask(null);
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

        foreach ($this->subtasks as $s) {
            $resources = $s->getAllResourcesAssigned();
            foreach ($resources as $r) {
                $result[$r->getId()] = $r;
            }
        }

        return $result;
    }
}
