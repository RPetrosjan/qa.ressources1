<?php

namespace Act\ResourceBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * Contient toutes les méthodes pour récupèrer une collection
 * de tâches dans la base de données.
 *
 */
class TaskRepository extends EntityRepository
{
    /**
     * Fonction utilisée pour vérifier que le planning affiche bien toutes les tâches qu'il devrait afficher
     * pour une équipe donnée, un projet donné, des dates de début et de fin données.
     * @param  \Project  $project
     * @param  \DateTime $start
     * @param  \DateTime $end
     * @param  \Team     $team
     * @return int
     */
    public function countTasksToShow(Project $project, \DateTime $start, \DateTime $end, Team $team = null)
    {
        $result = array();
        $qb = $this->createQueryBuilder('task')

                ->leftJoin('task.teams', 'tteam')
                ->leftJoin('task.teamprofiles', 'tteamprofile')

                ->andWhere('task.end >= :start')
                ->andWhere('task.start <= :end')
                ->andWhere('task.project = :project')

                ->setParameter('project', $project)
                ->setParameter('start', $start)
                ->setParameter('end', $end);

        // On récupère toutes les tâches de ce projet pour les dates données
        $tasks = $qb->getQuery()->getResult();

        // On réalise le tri selon l'équipe demandée
        foreach ($tasks as $task) {
            if ($task instanceof SubTask) {
                // Si c'est une sous-tâche, on vérifie qu'elle appartient à l'équipe ou qu'elle a un profil de l'équipe associé
                if ($task->belongsTo($team)) {
                    $result[] = $task;
                }
            } elseif ($task instanceof CommonTask) {
                // Si c'est une tâche, on vérifie qu'elle appartient à l'équipe ou qu'elle a un profil de l'équipe associé
                // ou bien qu'une de ses tâches fille remplisse ces conditions
                if ($task->belongsTo($team) || count($task->getSubTasksInvolving($team)) > 0) {
                    $result[] = $task;
                }
            } else {
                // Si c'est une métatâche, on vérifie qu'elle appartient à l'équipe ou qu'elle a un profil de l'équipe associé
                // ou bien qu'une de ses tâches fille remplisse ces conditions
                if ($task->belongsTo($team) || count($task->getCommonTasksInvolving($team)) > 0) {
                    $result[] = $task;
                }
            }
        }

        return count($result);
    }

    /**
     * Récupère la liste des tache d'une equipe
     * @param Team $t
     */
    public function getTasksofTeam(Team $t)
    {
        $qb = $this->createQueryBuilder('task')
                ->leftJoin('task.teams', 'tteam')
                ->where('tteam = :team')
                ->setParameter('team', $t);

        return $qb->getQuery()->getResult();
    }
}
