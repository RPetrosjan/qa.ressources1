<?php

namespace Act\ResourceBundle\Services\Project;

/**
 * Classe décrivant un planning de projet
 * Contient les informations utilisées par les vues du planning
 */
class Planning
{
    /**
     * Jours de début et de fin
     * @var Datetime
     */
    public $start;
    public $end;

    /**
     * Tableau contenant les jours
     * @var array
     */
    public $days = array();

    /**
     * Tableau contenant les mois
     * @var array
     */
    public $months = array();

    /**
     * Tableau contenant les semaines
     * @var array
     */
    public $weeks = array();

    /**
     * Tableau contenant les tâches du projet
     * @var array
     */
    private $tasks = array();

    /**
     * Le projet concerné
     * @var Project
     */
    public $project;

    /**
     * Les équipes concernées
     * @var Team
     */
    public $team;

    /**
     * Les données du planning
     * préparées pour l'affichage
     * @var array
     */
    public $data;

    /**
     * Nombre de tâches à afficher qui n'ont pas d'équipe
     * @var int
     */
    private $nbTasksWithoutTeam = null;

    // Dependencies
    private $tm; // DateManager
    private $em; // EntityManager

    public function __construct(\Act\MainBundle\Services\DateManager $tm, \Doctrine\ORM\EntityManager $em)
    {
        $this->tm = $tm;
        $this->em = $em;
    }

    /**
     * Renvoi toutes les données nécessaires à handsontable pour créé
     * le tableau des affectations d'une équipe
     *
     * @param  boolean    $encode doit on encoder en json les données ?
     * @return JSON|array
     */
    public function getAllData($encode = true)
    {
        $data = array();
        if(count($this->tasks) == 0) $this->generateTasks();
        $data['assignments'] = $this->getDataAsExcelArray(false);
        $data['days'] = $this->getColHeaderAsExcelArray(false);
        $data['resources'] = $this->getRowHeaderAsExcelArray(false);
        $data['metadata'] = $this->getMetaDataAsExcelArray(false);
        $data['project'] = array(
            'id' => $this->project->getId(),
            'name' => $this->project->getName(),
            'tasks' => $this->tasks
        );

        if($encode) $data = json_encode($data);

        return $data;
    }

    /**
     * Renvoi le nombre de jour du planning
     *
     * @return int
     */
    public function getNbDays()
    {
        return count($this->days);
    }

    /**
     * Renvoi le workload de la semaine de la date donnée
     * pour une ressource donnée
     *
     * @param \DateTime                           $date
     * @param \Act\ResourceBundle\Entity\Resource $resource
     *
     * @return int
     */
    public function getWeekWorkload(\DateTime $date, \Act\ResourceBundle\Entity\Resource $resource)
    {
        $weekWorkload = 0;

        // On récupère les dates de début et fin de semaine
        $weekDates = $this->tm->findFirstAndLastDaysOfWeek($date);
        $start = $weekDates['start'];
        $end = $weekDates['end'];

        // On créé l'intervale de temps
        $oneDay = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $oneDay, $end);

        // On itère sur les jours
        foreach ($period as $day) {
            $weekWorkload += $this->getDayWorkload($day, $resource);
        }

        return $weekWorkload;
    }

    /**
     * Renvoi le workload d'un jour donné et pour une ressource donnée
     *
     * @param \DateTime                           $date
     * @param \Act\ResourceBundle\Entity\Resource $resource
     *
     * @return int
     */
    public function getDayWorkload(\DateTime $date, \Act\ResourceBundle\Entity\Resource $resource)
    {
        $dateString = $date->format('d/m/Y');
        $dayWorkload = 0;

        // On ajoute l'affectation du jour de ce projet
        if(isset($this->data[$resource->getNameShort()]['assignments'][$dateString]['assignment']))
            $dayWorkload = $this->data[$resource->getNameShort()]['assignments'][$dateString]['assignment']->getWorkload();

        // Et on rajoute les affectation sur les autres projets de ce jour
        if (isset($this->data[$resource->getNameShort()]['otherProjects'][$dateString])) {
            foreach ($this->data[$resource->getNameShort()]['otherProjects'][$dateString] as $otherAssignment) {
                $dayWorkload += $otherAssignment->getWorkload();
            }
        }

        return $dayWorkload;
    }

    /**
     * Renvoi le nombre de tâche qui devraient être affichées pour ce projet, et
     * qui n'ont pas d'équipe assignée.
     * @return int
     */
    public function getNbTasksWithoutTeam()
    {
        if($this->nbTasksWithoutTeam == null) $this->nbTasksWithoutTeam = $this->em->getRepository('ActResourceBundle:Task')->countTasksToShow($this->project, $this->start, $this->end, null);

        return $this->nbTasksWithoutTeam;
    }

    /**
     * Renvoi le nombre de tâche qui devraient être affichées pour ce projet et cette équipe
     * @return int
     */
    public function getNbTasks()
    {
        return $this->em->getRepository('ActResourceBundle:Task')->countTasksToShow($this->project, $this->start, $this->end, $this->team);
    }

    /**
     * Formatage des tâches du projet
     */
    public function generateTasks()
    {
        // Mise en forme des tâches
        foreach ($this->project->getMetaTasks() as $mt) {
            $this->tasks['hierarchical'][$mt->getId()] = array();
            $this->tasks['hierarchical'][$mt->getId()]['id'] = $mt->getId();
            $this->tasks['hierarchical'][$mt->getId()]['name'] = $mt->getName();
            $this->tasks['hierarchical'][$mt->getId()]['sold'] = $mt->getWorkloadSold();
            $this->tasks['hierarchical'][$mt->getId()]['tasks'] = array();
            $this->tasks['simple'][$mt->getId()] = $this->tasks['hierarchical'][$mt->getId()];

            foreach ($mt->getCommontasks() as $ct) {
                $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()] = array();
                $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['id'] = $ct->getId();
                $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['name'] = $ct->getName();
                $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['sold'] = $ct->getWorkloadSold();
                $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['start'] = array(
                    'day' => $ct->getStart()->format('d'),
                    'month' => $ct->getStart()->format('m'),
                    'year' => $ct->getStart()->format('Y')
                );
                $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['end'] = array(
                    'day' => $ct->getEnd()->format('d'),
                    'month' => $ct->getEnd()->format('m'),
                    'year' => $ct->getEnd()->format('Y')
                );
                $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['tasks'] = array();
                $this->tasks['simple'][$ct->getId()] = $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()];

                foreach ($ct->getSubtasks() as $st) {
                    $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['tasks'][$st->getId()] = array();
                    $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['tasks'][$st->getId()]['id'] = $st->getId();
                    $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['tasks'][$st->getId()]['name'] = $st->getName();
                    $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['tasks'][$st->getId()]['sold'] = $st->getWorkloadSold();
                    $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['tasks'][$st->getId()]['start'] = array(
                        'day' => $st->getStart()->format('d'),
                        'month' => $st->getStart()->format('m'),
                        'year' => $st->getStart()->format('Y')
                    );
                    $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['tasks'][$st->getId()]['end'] = array(
                        'day' => $st->getEnd()->format('d'),
                        'month' => $st->getEnd()->format('m'),
                        'year' => $st->getEnd()->format('Y')
                    );
                    $this->tasks['simple'][$st->getId()] = $this->tasks['hierarchical'][$mt->getId()]['tasks'][$ct->getId()]['tasks'][$st->getId()];
                }
            }
        }
    }

    /**
     * Met en forme un tableau contenant les données des affectations du planning.
     * Ces données pourront être déplacées, ce sont donc l'affectation et ses tâches.
     *
     * @param  boolean    $encode faut il encoder le tableau en JSON ?
     * @return Array|JSON
     */
    private function getDataAsExcelArray($encode = true)
    {
        $dataExcel = array();

        foreach ($this->data as $short => $data) {
            $row = array();

            foreach ($data['assignments'] as $day => $dayData) {
                if (isset($dayData['assignment'])) {
                    // Ajout des données de l'affectation
                    $assignementData = array(
                        'comment' => $dayData['assignment']->getComment(),
                        'workload' => $dayData['assignment']->getWorkload(),
                        'id' => $dayData['assignment']->getId()
                    );

                    // Ajout de la tâche de l'affectation
                    if ($dayData['assignment']->getCommontask()) {
                        $assignementData['task'] = array(
                            'name' => $dayData['assignment']->getCommontask()->getName(),
                            'id'   => $dayData['assignment']->getCommontask()->getId(),
                            'sold' => $dayData['assignment']->getCommontask()->getWorkloadSold(),
                            'start' => array(
                                'day' => $dayData['assignment']->getCommontask()->getStart()->format('d'),
                                'month' => $dayData['assignment']->getCommontask()->getStart()->format('m'),
                                'year' => $dayData['assignment']->getCommontask()->getStart()->format('Y')
                            ),
                            'end' => array(
                                'day' => $dayData['assignment']->getCommontask()->getEnd()->format('d'),
                                'month' => $dayData['assignment']->getCommontask()->getEnd()->format('m'),
                                'year' => $dayData['assignment']->getCommontask()->getEnd()->format('Y')
                            )
                        );
                    }

                    // Ajout de la sous-tâche de l'affectation
                    if ($dayData['assignment']->getSubtask()) {
                        $assignementData['subtask'] = array(
                            'name' => $dayData['assignment']->getSubtask()->getName(),
                            'id'   => $dayData['assignment']->getSubtask()->getId(),
                            'sold' => $dayData['assignment']->getSubtask()->getWorkloadSold(),
                            'start' => array(
                                'day' => $dayData['assignment']->getSubtask()->getStart()->format('d'),
                                'month' => $dayData['assignment']->getSubtask()->getStart()->format('m'),
                                'year' => $dayData['assignment']->getSubtask()->getStart()->format('Y')
                            ),
                            'end' => array(
                                'day' => $dayData['assignment']->getSubtask()->getEnd()->format('d'),
                                'month' => $dayData['assignment']->getSubtask()->getEnd()->format('m'),
                                'year' => $dayData['assignment']->getSubtask()->getEnd()->format('Y')
                            )
                        );
                    }

                    $row[] = $assignementData;
                } else {
                    // Pas d'affectation, jour vide
                    $row[] = array();
                }
            }
            $dataExcel[] = $row;
        }

        if($encode) $dataExcel = json_encode($dataExcel);

        return $dataExcel;
    }

    /**
     * Met en forme un tableau contenant les métadonnées du planning.
     * Ces métadonnées ne pourront pas être copiées/collées : jours fériés,
     * ressources indisponibles, autres affectations...
     *
     * @param  boolean    $encode faut il encoder le tableau en JSON ?
     * @return Array|JSON
     */
    private function getMetaDataAsExcelArray($encode = true)
    {
        $dataExcel = array();

        foreach ($this->data as $short => $data) {
            $row = array();
            foreach ($data['assignments'] as $day => $dayData) {
                $dayArray = array(
                    'day' => $dayData['day'],
                    'week' => $dayData['day']['day']->format('W')
                );

                if (isset($dayData['type'])) {
                    $dayArray['type'] = $dayData['type'];
                }

                if (isset($dayData['bankholiday'])) {
                    $dayArray['bankholiday'] = $dayData['bankholiday']['name'];
                }

                if (isset($data['otherProjects'][$day])) {
                    foreach ($data['otherProjects'][$day] as $opa) {
                        $otherPAData = array(
                            'workload' => $opa->getWorkload(),
                            'project'  => $opa->getProject()->getName(),
                            'id'       => $opa->getProject()->getId()
                        );

                        // Ajout de la tâche de l'affectation
                        if ($opa->getCommontask()) {
                            $otherPAData['task'] = array(
                                'name' => $opa->getCommontask()->getName(),
                                'id'   => $opa->getCommontask()->getId(),
                                'sold' => $opa->getCommontask()->getWorkloadSold(),
                                'start' => array(
                                    'day' => $opa->getCommontask()->getStart()->format('d'),
                                    'month' => $opa->getCommontask()->getStart()->format('m'),
                                    'year' => $opa->getCommontask()->getStart()->format('Y')
                                ),
                                'end' => array(
                                    'day' => $opa->getCommontask()->getEnd()->format('d'),
                                    'month' => $opa->getCommontask()->getEnd()->format('m'),
                                    'year' => $opa->getCommontask()->getEnd()->format('Y')
                                )
                            );
                        }

                        // Ajout de la sous-tâche de l'affectation
                        if ($opa->getSubtask()) {
                            $otherPAData['subtask'] = array(
                                'name' => $opa->getSubtask()->getName(),
                                'id'   => $opa->getSubtask()->getId(),
                                'sold' => $opa->getSubtask()->getWorkloadSold(),
                                'start' => array(
                                    'day' => $opa->getSubtask()->getStart()->format('d'),
                                    'month' => $opa->getSubtask()->getStart()->format('m'),
                                    'year' => $opa->getSubtask()->getStart()->format('Y')
                                ),
                                'end' => array(
                                    'day' => $opa->getSubtask()->getEnd()->format('d'),
                                    'month' => $opa->getSubtask()->getEnd()->format('m'),
                                    'year' => $opa->getSubtask()->getEnd()->format('Y')
                                )
                            );
                        }

                        $dayArray['otherProjectsAssignments'][] = $otherPAData;
                    }
                }

                $row[] = $dayArray;
            }
            $dataExcel[] = $row;
        }

        if($encode) $dataExcel = json_encode($dataExcel);

        return $dataExcel;
    }

    /**
     * Met en forme un tableau contenant les données des jours du planning.
     * Ces données sont utilisées pour afficher la 1ère ligne du tableau.
     *
     * @param  boolean    $encode faut il encoder le tableau en JSON ?
     * @return Array|JSON
     */
    private function getColHeaderAsExcelArray($encode = true)
    {
        $row = array();
        foreach ($this->days as $date => $dayData) {
            $dayData['dayLong'] = $date;
            $dayData['details'] = array(
                'day' => $dayData['day']->format('d'),
                'month' => $dayData['day']->format('m'),
                'year' => $dayData['day']->format('Y')
            );
            $dayData['day'] = $dayData['day']->format('d/m');
            $row[] = $dayData;
        }

        if($encode) $row = json_encode($row);

        return $row;
    }

    /**
     * Met en forme un tableau contenant les données des ressources du planning.
     * Ces données sont utilisées pour afficher la 1ère colonne du tableau.
     *
     * @param  boolean    $encode faut il encoder le tableau en JSON ?
     * @return Array|JSON
     */
    private function getRowHeaderAsExcelArray($encode = true)
    {
        $row = array();
        foreach ($this->data as $resource => $data) {
            // Ajout des données de la resource
            $tmp = array(
                'nameShort' => $resource,
                'id' => $data['resource']->getId(),
                'resource' => array(
                    'name' => $data['resource']->getName(),
                    'daysPerWeek' => $data['resource']->getDaysPerWeek(),
                    'start' => $data['resource']->getStart()->format('d/m/Y'),
                    'location' => $data['resource']->getLocation()->getName()
                )
            );

            // Si date de fin précisée, on l'ajoute
            if ($data['resource']->getEnd()) {
                $tmp['resource']['end'] = $data['resource']->getEnd()->format('d/m/Y');
            }

            $row[] = $tmp;
        }

        if($encode) $row = json_encode($row);

        return $row;
    }
}
