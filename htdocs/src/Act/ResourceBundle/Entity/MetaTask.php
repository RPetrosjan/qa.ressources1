<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MetaTask entity code
 *
 * @ORM\Table(name="metatask")
 * @ORM\Entity(repositoryClass="Act\ResourceBundle\Entity\MetaTaskRepository")
 */
class MetaTask extends Task
{
    /**
     * @var ArrayCollection $commontasks children tasks
     *
     * @ORM\OneToMany(targetEntity="Act\ResourceBundle\Entity\CommonTask", mappedBy="metatask", cascade={"persist", "remove"})
     */
    protected $commontasks;

    public function __construct()
    {
        parent::__construct();
        $this->commontasks = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Renvoi la somme du workload affecté à cette tâche
     * @return float
     */
    public function getSumWorkloadAssigned()
    {
        $total = 0;
        foreach ($this->commontasks as $ct) {
            $total += $ct->getSumWorkloadAssigned();
        }

        return $total;
    }

    /**
     * Renvoi le total de workload affecté à cette tâche
     * @return float
     */
    public function getTotalAssigned()
    {
        return 0;
    }

    /**
     * Renvoi le nombre de ressources concernées par cette tâche
     * @return int
     */
    public function getNbResourcesInvolved()
    {
        return 0;
    }

    /**
     * Renvoi le nombre d'équipes concernées par cette tâche
     * @return int
     */
    public function getNbTeamsInvolved()
    {
        return 0;
    }

    /**
     * Add commontask
     *
     * @param  CommonTask $task
     * @return MetaTask
     */
    public function addCommontask(CommonTask $task)
    {
        $this->commontasks[] = $task;
        $task->setMetatask($this);

        return $this;
    }

    /**
     * Remove commontasks
     *
     * @param Act\ResourceBundle\Entity\CommonTask $commontasks
     */
    public function removeCommontask(\Act\ResourceBundle\Entity\CommonTask $commontasks)
    {
        $this->commontasks->removeElement($commontasks);
    }

    /**
     * Get commontasks
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getCommontasks()
    {
        return $this->commontasks;
    }

    public function countChildsInvolving(Team $team = null)
    {
        $nb = 0;
        foreach ($this->commontasks as $t) {
            $nb += $t->countChildsInvolving($team);
            if ($t->belongsTo($team)) {
                $nb++;
            }
        }

        return $nb;
    }

    public function getCommonTasksInvolving(Team $team = null)
    {
        $array = array();
        foreach ($this->commontasks as $ct) {
            if($ct->belongsTo($team) or $ct->hasSubTasksInvolving($team))
                $array[] = $ct;
        }

        return $array;
    }

    public function getMetaTask()
    {
        return $this;
    }

    public function getType()
    {
        return 'métatâche';
    }

    /**
     * Renvoi un tableau contenant les tâches appartenant à cette métatâche,
     * de manière ordonnée si plusieurs tâches peuvent se suivre
     * @return array
     */
    public function getCommonTasksOrdered(\DateTime $start, \DateTime $end, Team $team = null)
    {
        $task = null; $i = 0; $array = array(); $continue = true; $nbNull = 0;
        while ($continue) {
            $nextTask = $this->findNextCommonTask($start, $end, $task, $team);

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
     * Recherche la tâche qui suit au plus près la tâche donnée
     * @return CommonTask/null
     */
    private function findNextCommonTask(\DateTime $start, \DateTime $end, CommonTask $task = null, Team $team = null)
    {
        if ($task == null) {
            // On cherche la première tâche
            $minTask = null;
            foreach ($this->commontasks as $t) {
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

            return $minTask;
        } else {
            // On cherche la tâche la plus proche de la tâche donnée
            $minTask = null; $minDate = null;
            foreach ($this->commontasks as $t) {
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

            return $minTask;
        }
    }

    public function getNameForExcel()
    {
        return $this->name;
    }

    public function getCSSClass()
    {
        return 'metatask';
    }

    public function setStart(\DateTime $start, $force = false)
    {
        $lastStart = null;
        if($this->start != null)
            $lastStart = clone $this->start;

        parent::setStart($start);

        if ($force || $lastStart != null) {
            // Si on réduit la date, on doit répercuter les modifs
            if ($force || $lastStart < $this->start) {
                foreach ($this->commontasks as $ctask) {
                    if ($ctask->getStart() < $this->start) {
                        $ctask->setStart($this->start);
                    }
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
            // Si on réduit la date, on doit répercuter les modifs
            if ($force || $lastEnd > $this->end) {
                foreach ($this->commontasks as $ctask) {
                    if ($ctask->getEnd() > $this->end) {
                        $ctask->setEnd($this->end);
                    }
                }
            }
        }
    }

    public function getParent()
    {
        return null;
    }

    public function getTotalChildrenWorkloadSold()
    {
        $result = 0;
        foreach ($this->commontasks as $c) {
            $result += $c->getTotalChildrenWorkloadSold();
        }

        return $result;
    }

    /**
     * Returns all resources assigned to this task
     * @return array
     */
    public function getAllResourcesAssigned()
    {
        $result = array();

        foreach ($this->commontasks as $c) {
            $resources = $c->getAllResourcesAssigned();
            foreach ($resources as $r) {
                $result[$r->getId()] = $r;
            }
        }

        return $result;
    }
}
