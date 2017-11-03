<?php

namespace Act\ResourceBundle\Services;

/**
 * Service regroupant la logique liée aux projets
 *
 */
class Project
{
    protected $em;

    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Calcule le temps invendu pour toutes les affectations ayant le même projet,
     * les mêmes tâches que l'affectation donnée, pour mise à jour du planning.
     * @param \Act\ResourceBundle\Entity\Assignment $assignment
     */
    public function computeUnsold(\Act\ResourceBundle\Entity\Assignment $ass)
    {
        $array = array();

        $assignments = $this->em->getRepository('ActResourceBundle:Assignment')->findBy(
                array(
                    "project" => $ass->getProject(),
                    "commontask" => $ass->getCommontask(),
                    "subtask" => $ass->getSubtask()
                ));

        foreach ($assignments as $a) {
            // On exclu les cas où l'affectation $ass n'a aucune tâche, car on ne doit pas mettre à jour les autres
            // Dans ce même cas. Seulement l'affectation $ass doit être mise à jour.
            if(!($a->getId()!= $ass->getId() && $a->getSubtask() == null && $a->getCommontask() == null))
                $array[$a->getDay()->format('d-m-Y')] = $a->getUnsold();
        }

        return $array;
    }

    /**
     * Calcule le temps invendu pour toutes les affectations ayant le même projet,
     * les mêmes tâches que la tâche donnée, pour mise à jour du planning.
     * @param \Act\ResourceBundle\Entity\Assignment $assignment
     */
    public function computeUnsoldWithTask(\Act\ResourceBundle\Entity\Task $task)
    {
        $array = array();

        $assignments = $this->em->getRepository('ActResourceBundle:Assignment')->findBy(
                ($task instanceof \Act\ResourceBundle\Entity\CommonTask ? array(
                    "project" => $task->getProject(),
                    "commontask" => $task,
                    "subtask" => null
                ) : array(
                    "project" => $task->getProject(),
                    "subtask" => $task
                )));

        foreach ($assignments as $a) {
            $array[$a->getDay()->format('d-m-Y')] = $a->getUnsold();
        }

        return $array;
    }

    /**
     * Réalise le calcul du temps invendu avec deux tableaux de temps invendu calculés
     * avant et après la modification respectivement, avec la fonction "computeUnsold"
     * @param array $before
     * @param array $after
     */
    public function mergeUnsold(array $before, array $after)
    {
        $result = array();
        foreach ($before as $day => $unsold) {
            $result[$day]['before'] = $unsold;
        }
        foreach ($after as $day => $unsold) {
            $result[$day]['after'] = $unsold;
        }

        $total = 0;
        foreach ($result as $day => $array) {
            $total += (isset($array['after']) ? $array['after'] : 0) - (isset($array['before']) ? $array['before'] : 0);
        }
        $result['total'] = $total;

        return $result;
    }
}
