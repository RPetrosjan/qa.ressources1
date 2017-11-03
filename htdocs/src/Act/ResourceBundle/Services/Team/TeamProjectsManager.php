<?php

namespace Act\ResourceBundle\Services\Team;

use Act\ResourceBundle\Entity\Project;
use Act\ResourceBundle\Entity\Team;
use Application\Sonata\UserBundle\Entity\User;
use Act\ResourceBundle\Entity\TeamProject;
use Doctrine\ORM\EntityManager;

/**
 * Classe permettant de gérer les projets d'équipe de chaque utilisateur.
 *
 */
class TeamProjectsManager
{
    // Dependencies
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Récupére les projets d'équipe qu'à choisi cet utilisateur.
     */
    public function getTeamProjects(Team $team, User $user)
    {
        $projects = array();

        $teamProjects = $this->em->getRepository('ActResourceBundle:TeamProject')->getTeamProject($user, $team);
        if (!$teamProjects || count($teamProjects->getProjects()) == 0) {
            // Les projets d'équipe ne sont pas définis pour le moment, on charge les projets préférés par défaut
            $preferedProjects = $this->em->getRepository('ActResourceBundle:PreferedProject')->getPreferedProjectsOrdered($user);

            // On ajoute aux projets à renvoyer
            foreach ($preferedProjects as $pp) {
                $projects[$pp->getProject()->getName()] = $pp->getProject();
            }
        } else {
            // On ajoute aux projets à renvoyer
            foreach ($teamProjects->getProjects() as $pp) {
                $projects[$pp->getName()] = $pp;
            }
        }

        // Sort projects alphabetically
        ksort($projects);

        return $projects;
    }

    /**
     * Sauvegarde les projets d'équipe de cet utilisateur
     */
    public function saveTeamProjects(Team $team, User $user, array $projects)
    {
        $teamProjects = $this->em->getRepository('ActResourceBundle:TeamProject')->findOneBy(array('user' => $user, 'team' => $team));
        if (!$teamProjects) {
            // Crée une nouvelle entité
            $teamProjects = new TeamProject();
            $teamProjects->setUser($user);
            $teamProjects->setTeam($team);
        }

        $teamProjects->clearProjects();
        foreach ($projects as $project) {
            $teamProjects->addProject($project);
        }

        $this->em->persist($teamProjects);
        $this->em->flush();
    }

    /**
     * Vide les projets choisis par l'utilisateur
     */
    public function clearTeamProjects(Team $team, User $user)
    {
        $teamProjects = $this->em->getRepository('ActResourceBundle:TeamProject')->findOneBy(array('user' => $user, 'team' => $team));
        if ($teamProjects) {
            $teamProjects->clearProjects();
            $this->em->persist($teamProjects);
            $this->em->flush();
        }
    }
}
