<?php

namespace Act\ResourceBundle\Services;

use Act\ResourceBundle\Entity\Project;
use Application\Sonata\UserBundle\Entity\User;
use Act\ResourceBundle\Entity\Task;
use Act\ResourceBundle\Entity\SubTask;
use Act\ResourceBundle\Entity\CommonTask;
use Act\ResourceBundle\Entity\Assignment;

/**
 * Service regroupant la logique des tests des droits pour les CPT
 *
 */
class CPTRights
{
    /**
     * Détermine si l'utilisateur est CPT sur ce projet
     * @return boolean
     */
    public function isCPT(Project $p, User $u)
    {
        if ($u->getResource() == null) {
            return false;
        }

        $cpts = $p->getCpts();

        $isCPT = false;
        foreach ($cpts as $cpt) {
            if ($cpt->getResource()->getId() == $u->getResource()->getId()) {
                $isCPT = true; break;
            }
        }

        return $isCPT;
    }

    /**
     * Détermine si l'utilisateur à les droits CPT sur cette tâche
     * @return bool
     */
    public function hasAccess(Task $t, User $u)
    {
        $result = false;

        // On vérifie que l'utilisateur est CPT sur le projet
        if ($u->getResource() == null || !$this->isCPT($t->getProject(), $u)) {
            return $result;
        }

        // Un CPT n'a le droit de gérer que des sous-tâches
        if (!($t instanceof SubTask)) {
            return $result;
        }

        // Un CPT n'a le droit de gérer que les sous-tâches des équipes dont il est CPT
        foreach ($t->getProject()->getCpts() as $cpt) {
            if ($cpt->getResource()->getId() == $u->getResource()->getId() && $t->belongsTo($cpt->getTeam())) {
                $result = true; break;
            }
        }

        return $result;
    }

    /**
     * Détermine si l'utilisateur à le droit CPT de créer des tâches filles de celle ci
     * @return bool
     */
    public function canCreateChilds(Task $t, User $u)
    {
        $result = false;

        // On vérifie que l'utilisateur est CPT sur le projet
        if ($u->getResource() == null || !$this->isCPT($t->getProject(), $u)) {
            return $result;
        }

        // Un CPT n'a le droit de créer que des sous-tâches (donc fille de tâche)
        if (!($t instanceof CommonTask)) {
            return $result;
        }

        // Un CPT n'a le droit de créer que des sous-tâches, pour les tâches des équipes dont il est CPT
        foreach ($t->getProject()->getCpts() as $cpt) {
            if ($cpt->getResource()->getId() == $u->getResource()->getId() && $t->belongsTo($cpt->getTeam())) {
                $result = true; break;
            }
        }

        return $result;
    }

    /**
     * Détermine si un utilisateur à le droit de modifier la sous-tâche de l'affectation
     * @param \Act\ResourceBundle\Entity\Task            $t
     * @param \Application\Sonata\UserBundle\Entity\User $u
     */
    public function canChangeSubtask(Assignment $a, User $u)
    {
        $result = false;

        $task = $a->getCommontask();
        if ($task == null) {
            return $result;
        }

        // On vérifie que l'utilisateur est CPT sur le projet
        if ($u->getResource() == null || !$this->isCPT($task->getProject(), $u)) {
            return $result;
        }

        // On regarde si une sous-tâche est déjà associée, et si elle est modifiable par l'utilisateur
        $subtask = $a->getSubtask();
        if ($subtask != null) {
            $atLeastOne = false;
            foreach ($task->getProject()->getCpts() as $cpt) {
                if ($cpt->getResource()->getId() == $u->getResource()->getId() && $subtask->belongsTo($cpt->getTeam())) {
                    $atLeastOne = true; break;
                }
            }
            if (!$atLeastOne) {
                return $result;
            }
        }

        // Un CPT n'a le droit de choisir la sous-tâche, que pour les tâches des équipes dont il est CPT
        foreach ($task->getProject()->getCpts() as $cpt) {
            if ($cpt->getResource()->getId() == $u->getResource()->getId() && $task->belongsTo($cpt->getTeam())) {
                $result = true; break;
            }
        }

        return $result;
    }
}
