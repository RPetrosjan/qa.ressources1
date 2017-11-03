<?php

namespace Act\ResourceBundle\Services\Project;

use Act\ResourceBundle\Entity\Project;
use Act\ResourceBundle\Entity\Team;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Classe décrivant un planning de projet
 * Contient les informations utilisées par les vues du planning
 */
class PlanningManager
{
    /* Dependency */
    private $container;
    private $stopwatch = null;

    /**
     * Jours de début et de fin
     * @var Datetime
     */
    private $start;
    private $end;

    /**
     * Faut il cacher ou non les ressources
     * non pertinentes (pas encore embauché, départ...)
     * @var Boolean
     */
    private $hide;

    /**
     * Tableau contenant les jours
     * @var array
     */
    private $days = array();

    /**
     * Tableau contenant les mois
     * @var array
     */
    private $months = array();

    /**
     * Tableau contenant les semaines
     * @var array
     */
    private $weeks = array();

    /**
     * Le projet concerné
     * Variable utilisée si on veut charger plusieurs équipes pour un même projet.
     * @var Project
     */
    private $project;

    /**
     * Les projets concernés
     * Variable utilisée si on veut charger plusieurs projets pour une même équipe.
     * @var array
     */
    private $projects;

    /**
     * L'équipe concernée
     * Variable utilisée si on veut charger plusieurs projets pour une même équipe.
     * @var Teamq
     */
    private $team;

    /**
     * Les équipes concernées
     * Variable utilisée si on veut charger plusieurs équipes pour un même projet.
     * @var array
     */
    private $teams;

    /**
     * Les équipes qu'on ne charge pas directement
     * @var array
     */
    private $otherTeams;

    /**
     * Détermine quel planning sera renvoyé par getNextPlanning
     * @var int
     */
    private $currentIndex = 0;
    private $plannings = array();

    /**
     * Initialise un planning avec des informations de la requête
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->user = $this->container->get('security.context')->getToken()->getUser();

        if ($this->container->has('debug.stopwatch')) {
            $this->stopwatch = $this->container->get('debug.stopwatch');
            $this->stopwatch->start('PlanningManager constructor');
        }

        // Initialisation des paramètres de la requête
        $request = $this->container->get('request');
        $hide = ($request->query->get('hide') != null ? $request->query->get('hide') : $request->request->get('hide'));
        (isset($hide) && $hide == 1 ? $this->hide = true : $this->hide = false);
        $start = ($request->query->get('start') != null ? $request->query->get('start') : $request->request->get('start'));
        (isset($start) ? $this->start = \DateTime::createFromFormat('d/m/Y', $start) : $this->start = null);
        $end = ($request->query->get('end') != null ? $request->query->get('end') : $request->request->get('end'));
        (isset($end) ? $this->end = \DateTime::createFromFormat('d/m/Y', $end) : $this->end = null);

        $this->initDateVars();
        $this->initDaysArray();
        $this->initMonthsArray();
        $this->initWeeksArray();
        $this->addBeforeCurrentClasses();

        if ($this->stopwatch != null) {
            $this->stopwatch->stop('PlanningManager constructor');
        }
    }

    /**
     * Défini quel projet doit être utilisé pour remplir le planning.
     * Utilisé si on veut charger plusieurs équipes pour un seul projet.
     *
     * @param \Act\ResourceBundle\Entity\Project $p
     * @param \Act\ResourceBundle\Entity\Team $t
     */
    public function setProject(Project $p, Team $t = null)
    {
        // Réinitialisation des variables inutiles
        $this->team = null;
        $this->projects = null;

        if ($this->stopwatch != null) {
            $this->stopwatch->start('PlanningManager setProject');
        }

        $this->project = $p;

        // Chargement des tâches du projet de manière performante
        $this->project->setMetaTasks($this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:MetaTask')->getMetaTasksForProject($this->project));

        // Chargement des affectations des autres projets (pour highlight des erreurs)
        $this->otherAssignments = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:Assignment')->getAssignmentsNotThisProject($this->project, $this->start, $this->end);

        // Préparation des équipes à charger
        if (!is_null($t)) {
            $teamsToLoad = array($t->getId());
        } else {
            $teamsToLoad = array();
            $preferedTeams = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:PreferedTeam')->findBy(
              array('user' => $this->user, 'project' => $this->project)
            );
            if (count($preferedTeams) > 0) {
                foreach ($preferedTeams as $prefT) {
                    $teamsToLoad[] = $prefT->getTeam()->getId();
                }
            } else {
                if ($this->user->getResource()) {
                    $teamsToLoad[] = $this->user->getResource()->getTeam()->getId();
                }
            }
        }

        // Chargement des ressources et affectations des équipes
        if ($this->hide) {
            // On ne charge que les ressource ayant déjà des affectations sur le projet
            $this->teams = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:Team')
                    ->getTeamsResourcesAssignmentsForProject($this->project, $this->start, $this->end, $teamsToLoad);
        } else {
            // On charge toutes les ressources de l'équipe
            $this->teams = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:Team')
                    ->getTeamsManagerResourcesLocation($this->start, $this->end, $teamsToLoad);
        }

        // On cherche les équipes à charger en ajax
        $otherTeams = array();
        $teams = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:Team')->findAll();
        foreach ($teams as $team) {
            $add = true;
            foreach ($teamsToLoad as $ttl) {
                if ($ttl == $team->getId()) {
                    $add = false;
                }
            }

            if ($add) {
                $otherTeams[] = $team;
            }
        }
        $this->otherTeams = $otherTeams;

        if ($this->stopwatch != null) {
            $this->stopwatch->stop('PlanningManager setProject');
        }
    }

    /**
     * Défini quelle équipe doit être utilisée pour remplir le planning.
     * Utilisé si on veut charger plusieurs projets pour une seule équipe.
     *
     * @param \Act\ResourceBundle\Entity\Project $p
     */
    public function setTeam(Team $t)
    {
        // Réinitialisation des variables inutiles
        $this->teams = null;
        $this->project = null;

        if ($this->stopwatch != null) {
            $this->stopwatch->start('PlanningManager setTeam');
        }

        // Chargement des ressources et affectations de l'équipe
        $this->team = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:Team')
                ->getTeamsManagerResourcesLocation($this->start, $this->end, array($t->getId()));
        $this->team = array_shift($this->team);

        // Chargement des projets à afficher pour cet utilisateur/équipe
        $this->projects = array();
        $projects = $this->container->get('act_resource.team.team_projects_manager')->getTeamProjects($t, $this->user);
        foreach ($projects as $p) {
            // Chargement des tâches du projet de manière performante
            $p->setMetaTasks($this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:MetaTask')->getMetaTasksForProject($p));
            $this->projects[] = $p;
        }

        // Chargement des affectations des autres projets (pour highlight des erreurs)
        $this->otherAssignments = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:Assignment')->getAssignmentsNotTheseProjects($this->projects, $this->start, $this->end);

        if ($this->stopwatch != null) {
            $this->stopwatch->stop('PlanningManager setTeam');
        }
    }

    /**
     * Renvoi tous les plannings des équipes qui doivent être chargées
     *
     * @return array
     */
    public function getPlannings()
    {
        if (count($this->plannings) == 0) {
            $plannings = array();

            while (($planning = $this->getNextPlanning()) != null) {
                $plannings[] = $planning;
            }

            $this->plannings = $plannings;
        }

        return $this->plannings;
    }

    /**
     * Renvoi un tableau des planning des équipes qui ne sont pas chargées
     * et qu'il faut charger en AJAX
     *
     * @return array
     */
    public function getUnloadedTeamsPlannings()
    {
        $plannings = array();

        foreach ($this->otherTeams as $team) {
            $planning = new Planning($this->container->get('act_main.date.manager'), $this->container->get('doctrine')->getManager());
            $planning->team = $team;
            $planning->project = $this->project;

            $plannings[] = $planning;
        }

        return $plannings;
    }

    /**
     * Retourne le planning de l'équipe donnée
     *
     * @param  Team     $team
     * @return Planning
     */
    public function getTeamPlanning(Team $team)
    {
        return $this->generatePlanning($team, $this->project);
    }

    /**
     * Retourne le planning du projet donné
     *
     * @param  Project  $project
     * @return Planning
     */
    public function getProjectPlanning(Project $project)
    {
        return $this->generatePlanning($this->team, $project);
    }

    /**
     * Retourne le prochain planning
     * @return Planning
     */
    private function getNextPlanning()
    {
        $planning = null;

        if ($this->team != null && $this->teams == null) {
            // On veut charger les projets pour une seule équipe
            if (!isset($this->projects[$this->currentIndex])) {
                return null;
            }

            $project = $this->projects[$this->currentIndex];
            $planning = $this->generatePlanning($this->team, $project);
            $this->currentIndex++;

        } else {
            // On veut charger les équipes pour un seul projet
            if (!isset($this->teams[$this->currentIndex])) {
                return null;
            }

            $team = $this->teams[$this->currentIndex];
            $planning = $this->generatePlanning($team, $this->project);
            $this->currentIndex++;
        }

        return $planning;
    }

    /**
     * Retourne un objet planning contenant toutes les infos nécessaires
     * pour l'affichage dans le template
     *
     * @return Planning
     */
    private function generatePlanning(Team $team, Project $project)
    {
        if ($this->stopwatch != null) {
            $this->stopwatch->start('PlanningManager generatePlanning '.$team->getId());
        }

        // Prépare l'objet planning
        $planning = new Planning($this->container->get('act_main.date.manager'), $this->container->get('doctrine')->getManager());

        $planning->days = $this->days; // Ce tableau peut changer : temps invendu, on passe une copie
        $planning->weeks = &$this->weeks; // Ce tableau ne change pas, on passe la référence
        $planning->months = &$this->months; // Ce tableau ne change pas, on passe la référence

        $planning->start = $this->start; // Datetime par défaut sont passées par référence
        $planning->end = $this->end;

        $planning->team = $team;
        $planning->project = $project;

        // Initialisation des données
        $data = array();

        // Boucle sur les ressources de l'équipe
        foreach ($team->getResources() as $resource) {
            if(!isset($data[$resource->getNameShort()]))
                $data[$resource->getNameShort()] = array();

            // Ajout des données de la ressource
            $data[$resource->getNameShort()]['resource'] = $resource;
            $data[$resource->getNameShort()]['assignments'] = array();

            // Chargement des affectations des autres projets pour cette ressource
            $otherAssignments = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:Assignment')->getResourceAssignmentsForProject($project, $resource, $this->start, $this->end, true, false);
            $data[$resource->getNameShort()]['otherProjects'] = array();

            // Création du tableau des affectations avec la date en clé de tableau
            // pour accélèrer la suite des traitements
            foreach ($otherAssignments as $oa) {
                if (!isset($data[$resource->getNameShort()]['otherProjects'][$oa->getDay()->format('d/m/Y')])) {
                    $data[$resource->getNameShort()]['otherProjects'][$oa->getDay()->format('d/m/Y')] = array();
                }
                $data[$resource->getNameShort()]['otherProjects'][$oa->getDay()->format('d/m/Y')][$oa->getId()] = $oa;
            }

            // On récupère les affectations qui concernent cette ressource sur ce projet
            $tmp = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:Assignment')->getResourceAssignmentsForProject($project, $resource, $this->start, $this->end, false, true);
            $assignments = array();

            // Création du tableau des affectations avec la date en clé de tableau
            // pour accélèrer la suite des traitements
            foreach ($tmp as $a) {
                $assignments[$a->getDay()->format('d/m/Y')] = $a;
            }

            // On prépare les évènements de chaque jour pour la ressource
            foreach ($this->days as $dayDate => $dayData) {
                // Initialisation du tableau des données du jour
                $data[$resource->getNameShort()]['assignments'][$dayDate] = array();

                // Ajout des données liées au jour concerné
                $data[$resource->getNameShort()]['assignments'][$dayDate]['day'] = $dayData;

                // On vérifie que la ressource est active ce jour là
                if (!$resource->isAvailable($dayData['day'], $dayData['day'])) {
                    $data[$resource->getNameShort()]['assignments'][$dayDate]['type'] = 'disabled';
                }

                // On regarde si ce jour correspond à un jour férié
                if (isset($dayData['bankholiday'])) {
                    if (isset($dayData['bankholiday'][$resource->getLocation()->getName()])) {
                        // Ce jour est férié pour cette ressource
                        $data[$resource->getNameShort()]['assignments'][$dayDate]['type'] = 'bankholiday';
                        $data[$resource->getNameShort()]['assignments'][$dayDate]['bankholiday'] = $dayData['bankholiday'];
                    }
                }

                // On cherche si il existe une affectation ce jour ci
                if (isset($assignments[$dayDate])) {
                    // Une affectation est présente
                    if(!isset($data[$resource->getNameShort()]['assignments'][$dayDate]['type']))
                        $data[$resource->getNameShort()]['assignments'][$dayDate]['type'] = 'assignment';

                    // On l'ajoute au tableau
                    $data[$resource->getNameShort()]['assignments'][$dayDate]['assignment'] = $assignments[$dayDate];

                    // Si l'affectation est considérée comme invendue
                    $unsold = $assignments[$dayDate]->getUnsold();
                    if ($unsold > 0) {
                        // On le signale...
                        $planning->days[$dayDate]['unsold'] += $unsold;
                    }
                } // Fin if assignment
            } // Fin foreach days
        } // Fin foreach resources

        $planning->data = $data;

        if ($this->stopwatch != null) {
            $this->stopwatch->stop('PlanningManager generatePlanning '.$team->getId());
        }

        return $planning;
    }

    /**
     * Initialise les dates utilisées
     * dans le planning
     */
    private function initDateVars()
    {
        // Préparation de l'affichage par défaut si pas de jours choisis
        if ($this->start == null || $this->end == null) {
            $weeksBefore = $this->container->getParameter('act.planning.weeks_before');
            $weeksAfter = $this->container->getParameter('act.planning.weeks_after');
            $dates = $this->container->get('act_main.date.manager')->findFirstAndLastDaysOfWeek();

            $this->start = $dates['start']->sub(new \DateInterval('P'.$weeksBefore.'W'));
            $this->end   = $dates['end']->add(new \DateInterval('P'.$weeksAfter.'W'));
        }

        // Suppression des informations temporelles des dates
        $this->start->setTime(0,0,0);
        $this->end->setTime(0,0,0);

        $this->now = new \DateTime('now');
        $this->now->setTime(0,0,0);
    }

    /**
     * Initialise un tableau des jours
     * pour afficher le header du tableau
     * dans la vue concernée
     *
     * @return array
     */
    private function initDaysArray()
    {
        $oneDay = new \DateInterval('P1D');
        $nowFormat = $this->now->format('d/m/Y');

        // On fait en sorte d'avoir au moins deux jours ouvrés
        if ($this->start == $this->end) {
            do {
                $this->end->add($oneDay);
            } while ($this->end->format('N') > 5);
        }

        // On ajoute un jour pour inclure le jour de fin dans la période
        do {
            $this->end->add($oneDay);
        } while ($this->end->format('N') > 5);

        // On créé la période
        $period = new \DatePeriod($this->start, $oneDay, $this->end);

        // On charge les jours fériés
        $bankholidays = $this->container->get('doctrine')->getManager()->getRepository('ActResourceBundle:BankHoliday')->getBankHolidaysWithLocations($this->start, $this->end);

        // On ne garde que les jours ouvrés
        foreach ($period as $day) {
            $dayWeekNum = $day->format('N');
            $dayFormat = $day->format('d/m/Y');

            // On exclut les jours de week end
            if ($dayWeekNum < 6) {
                $this->days[$dayFormat] = array(
                    'day' => $day->setTime(0, 0, 0),
                    'unsold' => 0,
                );

                // On cherche les jours fériés présents à ce jour
                foreach ($bankholidays as $bk) {
                    if ($bk->getStart()->format('d/m/Y') == $dayFormat) {
                        if (!isset($this->days[$dayFormat]['bankholiday'])) {
                            $this->days[$dayFormat]['bankholiday'] = array();
                            $this->days[$dayFormat]['bankholiday']['name'] = $bk->getName();
                        }

                        foreach ($bk->getLocations() as $loc) {
                            if (!isset($this->days[$dayFormat]['bankholiday'][$loc->getName()])) {
                                $this->days[$dayFormat]['bankholiday'][$loc->getName()] = 0;
                            }

                            $this->days[$dayFormat]['bankholiday'][$loc->getName()] += 1;
                        }
                    }
                }

                // On ajoute les infos de début/fin de semaine
                if ($dayWeekNum == 1) {
                    $this->days[$dayFormat]['firstOfWeek'] = true;
                } elseif ($dayWeekNum == 5) {
                    $this->days[$dayFormat]['lastOfWeek'] = true;
                }

                if ($dayFormat == $nowFormat) {
                    $this->days[$dayFormat]['current'] = true;
                }
            }
        }

        // On enlève un jour pour remettre le jour de fin à celui choisi
        do {
            $this->end->sub(new \DateInterval('P1D'));
        } while ($this->end->format('N') > 5);
    }

    /**
     * Initialise un tableau des mois
     * pour afficher le header du tableau
     * dans la vue concernée
     *
     * @return array
     */
    private function initMonthsArray()
    {
        $this->months = array();
        $currentMonth = $this->now->format('m/Y');

        foreach ($this->days as $day) {
            $year = $day['day']->format('Y');
            $dayMonth = $day['day']->format('F');
            $month = $day['day']->format('m/Y');

            if (!isset($this->months[$year][$dayMonth])) {
                $this->months[$year][$dayMonth]['nb'] = 1;
            } else {
                $this->months[$year][$dayMonth]['nb']++;
            }

            if ($month == $currentMonth) {
                $this->months[$year][$dayMonth]['current'] = true;
            }
        }
    }

    /**
     * Initialise un tableau des semaines
     * pour afficher le header du tableau
     * dans la vue concernée
     *
     * @return array
     */
    private function initWeeksArray()
    {
        $this->weeks = array();

        foreach ($this->days as $date => $day) {
            $year = $day['day']->format('Y');
            $dayWeek = $day['day']->format('W');

            // Pour traiter les deux cas : semaine 1 du début d'année et semaine 1 de fin d'année (année d'après)
            if ($dayWeek == 1) {
                $tmp = clone $day['day'];
                if ($tmp->add(new \DateInterval('P7D'))->format('Y') == $year + 1) {
                    $dayWeek = '1';
                }
            }

            if (!isset($this->weeks[$year][$dayWeek])) {
                $this->weeks[$year][$dayWeek]['nb'] = 1;
            } else {
                if ($this->weeks[$year][$dayWeek]['nb'] == 5) {
                    // On a déjà les 5 jours de la semaine ! c'est en fait la première semaine de l'année suivante
                    // On met 6 jours à la semaine 52...
                    if(!isset($this->weeks[($year + 1)][$dayWeek]))
                        $this->weeks[($year + 1)][$dayWeek]['nb'] = 1;
                    else
                        $this->weeks[($year + 1)][$dayWeek]['nb']++;
                } else {
                    $this->weeks[$year][$dayWeek]['nb']++;
                }
            }

            if ($this->container->get('act_main.date.manager')->belongsToCurrentWeek($day['day'])) {
                $this->days[$date]['currentWeek'] = true;
                $this->weeks[$year][$dayWeek]['current'] = true;
            }
        }
    }

    /**
     * Ajoute des classes "beforeCurrent" à chaque semaine et jour qui précède directement
     * un jour/semaine de la semaine courante.
     */
    private function addBeforeCurrentClasses()
    {
        $last_year = null;
        $last_index = null;
        foreach ($this->weeks as $year => $weeks) {
            foreach ($weeks as $nb => $week) {
                if (isset($week['current'])) {
                    if($last_index != null) $this->weeks[$last_year][$last_index]['beforeCurrent'] = true;
                    break;
                } else {
                    $last_year = $year;
                    $last_index = $nb;
                }
            }
        }

        foreach ($this->days as $date => $day) {
            if (isset($day['currentWeek'])) {
                if($last_index != null) $this->days[$last_index]['beforeCurrent'] = true;
                break;
            } else {
                $last_index = $date;
            }
        }
    }

    public function getStart()
    {
        return clone $this->start;
    }

    public function getEnd()
    {
        return clone $this->end;
    }

    public function getHide()
    {
        return $this->hide;
    }

    public function isCPTOfProject()
    {
        return $this->container->get('act.cptRights')->isCPT($this->project, $this->user);
    }

    public function isCPT(Project $project)
    {
        return $this->container->get('act.cptRights')->isCPT($project, $this->user);
    }

    public function getNbWorkingDaysBetween(\DateTime $d1, \DateTime $d2)
    {
        return $this->container->get('act_main.date.manager')->getNbWorkingDaysBetween($d1, $d2);
    }

    public function canChangeSubtask(\Act\ResourceBundle\Entity\Assignment $a)
    {
        return $this->container->get('act.cptRights')->canChangeSubtask($a, $this->user);
    }
}
