<?php

namespace Act\ResourceBundle\Entity;

use Application\Sonata\UserBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Assignment Entity
 * An assignment represents a workload for a given day, project and resource.
 *
 * @ORM\Table(name="assignment", uniqueConstraints={@ORM\UniqueConstraint(name="assignment_day_ress_proj", columns={"day", "resource_id", "project_id"})})
 * @ORM\Entity(repositoryClass="Act\ResourceBundle\Entity\AssignmentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Assignment
{
    /**
     * @var integer the assignment id
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var float the assignment workload (in day fraction)
     * @example 0.5 = half a day
     * @Assert\Range(
     *      min = "0",
     *      max = "2"
     * )
     * @Assert\Type(type="numeric")
     * @ORM\Column(name="workload_assigned", type="decimal", scale=2)
     */
    private $workload_assigned;

    /**
     * @var \DateTime the assignment day
     * @ORM\Column(name="day", type="date")
     */
    private $day;

    /**
     * @var Resource the assignment resource
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Resource", inversedBy="assignments")
     * @ORM\JoinColumn(name="resource_id", referencedColumnName="id", nullable=false)
     */
    private $resource;

    /**
     * @var Project the assignment project
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\Project", inversedBy="assignments")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id", nullable=false)
     */
    private $project;

    /**
     * @var CommonTask the assignment common task
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\CommonTask", inversedBy="assignments")
     * @ORM\JoinColumn(name="commontask_id", referencedColumnName="id", nullable=true)
     */
    private $commontask;

    /**
     * @var SubTask the assignment sub task
     * @ORM\ManyToOne(targetEntity="Act\ResourceBundle\Entity\SubTask", inversedBy="assignments")
     * @ORM\JoinColumn(name="subtask_id", referencedColumnName="id", nullable=true)
     */
    private $subtask;

    /**
     * @var \DateTime Assignment creation date
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime Last assignment update date
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updated;

    /**
     * @var string comment of this assignment
     * @ORM\Column(name="comment_text", type="text", nullable=true)
     */
    private $comment;

    /**
     * Construct a new Assignment object
     * Initialize the updated and created dates
     */
    public function __construct()
    {
        $this->updated = null;
        $this->created = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    }

    /**
     * Returns the assignment resource
     * @return Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Define the assignment resource
     * @param Resource $resource
     */
    public function setResource(Resource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * Returns the assignment project
     *
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Define the assignment project
     *
     * @param Project $project
     */
    public function setProject(Project $project)
    {
        $this->project = $project;
        $project->addAssignment($this);
    }

    /**
     * Get the assignment id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the assignment workload.
     *
     * @param float $workload
     */
    public function setWorkload($workload)
    {
        $this->workload_assigned = (float) $workload;
    }

    /**
     * Adds some workload.
     *
     * @param float $nb
     */
    public function addWorkload($nb)
    {
        $this->workload_assigned += (float) $nb;
    }

    /**
     * Returns the workload for this assignment.
     *
     * @return float
     */
    public function getWorkload()
    {
        return (float) $this->workload_assigned;
    }

    /**
     * Define the assignment day
     *
     * @param DateTime $day
     */
    public function setDay(\DateTime $day)
    {
        $this->day = clone $day;
        $this->day->setTime(0, 0, 0);
    }

    /**
     * Returns the assignment day
     *
     * @return DateTime
     */
    public function getDay()
    {
        if ($this->day != null) {
            return clone $this->day;
        } else {
            return null;
        }
    }

    /**
     * Shift the assignment X working days in the past or future
     *
     * @param int $nbdays        the number X of days to shift the assignment
     * @param int $timeDirection 0 = shift in the past, 1 = shift in the future
     *
     * @return Assignment
     */
    public function shift($nbdays, $timeDirection)
    {
        $interval = \DateInterval::createFromDateString('1 day');
        $day = clone $this->day;
        $i = 0;

        if ($timeDirection == 0) {
            // In the past
            while ($i < $nbdays) {
                // Do not count weekends days
                $day->sub($interval);
                if ($day->format('N') < 6) {
                    $i++;
                }
            }
        } else {
            // In the future
            while ($i < $nbdays) {
                // Do not count weekends days
                $day->add($interval);
                if ($day->format('N') < 6) {
                    $i++;
                }
            }
        }

        $this->day = $day;

        return $this;
    }

    /**
     * Set the assignment common task
     * This function does not add the assignment to the task
     *
     * @param CommonTask $commontask
     */
    public function setCommontask(CommonTask $t = null)
    {
        $this->commontask = $t;
    }

    /**
     * Get the assignment common task
     *
     * @return CommonTask
     */
    public function getCommontask()
    {
        return $this->commontask;
    }

    /**
     * Set the assignment sub task
     *
     * @param SubTask $subtask
     */
    public function setSubtask(SubTask $t = null)
    {
        $this->subtask = $t;
    }

    /**
     * Get the assignment sub task
     *
     * @return SubTask
     */
    public function getSubtask()
    {
        return $this->subtask;
    }

    /**
     * Défini la tâche et sous tâche liée automatiquement à partir d'une seule tâche ou sous-tâche
     * @param \Act\ResourceBundle\Entity\Task $task
     */
    public function setTask(Task $task = null)
    {
        if ($this->subtask != null) {
            $this->subtask->removeAssignment($this);
        }

        if ($this->commontask != null) {
            $this->commontask->removeAssignment($this);
        }

        if ($task == null) {
            $this->subtask = null;
            $this->commontask = null;
        } else {
            if ($task instanceof SubTask) {
                $this->subtask = $task;
                $this->subtask->addAssignment($this);
                $this->commontask = $task->getCommontask();
            } elseif ($task instanceof CommonTask) {
                $this->subtask = null;
                $this->commontask = $task;
                $this->commontask->addAssignment($this);
            }
        }
    }

    /**
     * Retourne le temps invendu de l'affectation si il en existe
     * - elle n'a pas de tâche liée
     * - la tâche liée a déjà plus de temps assigné que de temps vendu
     * @return float
     */
    public function getUnsold()
    {
        $result = 0;

        if ($this->commontask == null) {
            // pas de tâche liée, donc invendu
            $result = $this->workload_assigned;
        } else {
            $task = $this->commontask;
            if ($this->subtask != null) {
                $task = $this->subtask;
            }

            $total = $task->getSumWorkloadAssigned();
            if ($total > $task->getWorkloadSold()) {
                // le temps affecté est supérieur au temps vendu,
                // on regarde si l'affectation est après d'autres affectations qu'on considère comme "vendues"
                $assignments = $task->getAssignments();
                $cumul = 0;

                // On calcule le total du temps des affectations avant celle ci
                foreach ($assignments as $ass) {
                    if ($ass->getDay() < $this->getDay()) {
                        $cumul += $ass->getWorkload();
                    }
                }

                if ($cumul + $this->getWorkload() > $task->getWorkloadSold()) {
                    // D'autres affectations avant celle-ci sont considérées comme vendues
                    // Mais celle-ci est considérée comme invendue, ou partiellement invendue
                    $unsold = null;
                    if ($cumul > $task->getWorkloadSold()) {
                        $unsold = $this->getWorkload();
                    } else {
                        $unsold = (($cumul + $this->getWorkload()) - $task->getWorkloadSold());
                    }
                    $result = $unsold;
                } else {
                    $result = 0;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the last update date
     * @codeCoverageIgnore
     * @return DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set the updated date.
     *
     * @param \DateTime $updated
     */
    public function setUpdated(\DateTime $updated = null)
    {
        $this->updated = $updated;
    }

    /**
     * Returns the creation date
     * @codeCoverageIgnore
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Determine if the assignment is out of its common task dates
     *
     * @return boolean
     */
    public function isOutOfTaskDates()
    {
        $result = false;

        if ($this->commontask != null) {
            if ($this->day < $this->commontask->getStart() || $this->day > $this->commontask->getEnd()) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Determine if the assignment is out of its sub task dates
     *
     * @return boolean
     */
    public function isOutOfSubtaskDates()
    {
        $result = false;

        if ($this->subtask != null) {
            if ($this->day < $this->subtask->getStart() || $this->day > $this->subtask->getEnd()) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Called by Doctrine when entity is created
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $this->updated = null;
    }

    /**
     * Set the assignment comment
     *
     * @param string comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get the assignment comment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Checks if this assignments has the same data as an other one
     * @param  Assignment $other
     * @return bool
     */
    public function hasSameData(Assignment $other)
    {
        // Check day
        if ($this->getDay() != null && $other->getDay() != null) {
            if ($this->getDay()->format('d/m/Y') != $other->getDay()->format('d/m/Y')) {
                return false;
            }
        }

        // Check project
        if ($this->getProject() != null && $other->getProject() != null) {
            if ($this->getProject()->getId() != $other->getProject()->getId()) {
                return false;
            }
        } elseif ($this->getProject() == null xor $other->getProject() == null) {
            return false;
        }

        // Check resource
        if ($this->getResource() != null && $other->getResource() != null) {
            if ($this->getResource()->getId() != $other->getResource()->getId()) {
                return false;
            }
        } elseif ($this->getResource() == null xor $other->getResource() == null) {
            return false;
        }

        // Check workload
        if ($this->getWorkload() != $other->getWorkload()) {
            return false;
        }

        // Check task
        if ($this->getCommontask() != null && $other->getCommontask() != null) {
            if ($this->getCommontask()->getId() != $other->getCommontask()->getId()) {
                return false;
            }
        } elseif ($this->getCommontask() == null xor $other->getCommontask() == null) {
            return false;
        }

        // Check subtask
        if ($this->getSubtask() != null && $other->getSubtask() != null) {
            if ($this->getSubtask()->getId() != $other->getSubtask()->getId()) {
                return false;
            }
        } elseif ($this->getSubtask() == null xor $other->getSubtask() == null) {
            return false;
        }

        // Check comment
        if ($this->getComment() != $other->getComment()) {
            return false;
        }

        // Same data if we are here
        return true;
    }

    /**
     * Copy the assignment data into an other one
     * @param Assignment $other
     */
    public function copyData(Assignment $other)
    {
        $this->setDay($other->getDay());
        $this->setProject($other->getProject());
        $this->setResource($other->getResource());
        $this->setWorkload($other->getWorkload());
        $this->setCommontask($other->getCommontask());
        $this->setSubtask($other->getSubtask());
        $this->setComment($other->getComment());
    }
}
