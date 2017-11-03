<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Act\ResourceBundle\Entity\Team;

/**
 * Controlleur de gestion des équipes
 */
class TeamController extends Controller
{
    /**
     * Affichage de la page des projets par équipe.
     * On affiche une seule équipe par page.
     *
     * Possibilité de paramétrer les projets à afficher par équipe.
     * Possibilité d'enregistrer ce paramétrage.
     *
     * Par défaut, on affiche les projets préférés.
     *
     * @param \Act\ResourceBundle\Entity\Team $team
     *
     * @return Response
     */
    public function projectsAction(Team $team)
    {
        $em = $this->container->get('doctrine')->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();
        $request = $this->container->get('request');
        $teamProjectsManager = $this->container->get('act_resource.team.team_projects_manager');

        // On vérifie si on veut réinitialiser l'affichage
        if ($request->query->get('reset') != null) {
            $teamProjectsManager->clearTeamProjects($team, $user);

            return $this->redirect($this->generateUrl('act_resource_team_projects', array('id' => $team->getId())));
        }

        // On vérifie si des projets ont été soumis par le formulaire
        if ($request->query->get('projects') != null && count($request->query->get('projects')) > 0) {
            $projectsToSave = array();
            $projectsIds = $request->query->get('projects');
            foreach ($projectsIds as $pid) {
                $project = $em->getRepository('ActResourceBundle:Project')->find($pid);
                if ($project) {
                    $projectsToSave[] = $project;
                }
            }

            $teamProjectsManager->saveTeamProjects($team, $user, $projectsToSave);
        }

        // On charge la liste de tous les projets pour le choix de l'affichage
        $projects = $em->getRepository('ActResourceBundle:Project')->findAll();

        // On charge les projets déjà choisis
        $chosenProjects = $teamProjectsManager->getTeamProjects($team, $user);

        // Création du planingManager
        $planningManager = $this->container->get('act_resource.planning_manager');
        $planningManager->setTeam($team);

        // Traitement du "referer"
        $referer = $this->container->get('request')->query->get('referer');
        if($referer == null)
            $referer = $this->generateUrl('act_resource_home');

        return $this->render('ActResourceBundle:Team:projects.html.twig', array(
            'manager'        => $planningManager,
            'team'           => $team,
            'projects'       => $projects,
            'chosenProjects' => $chosenProjects,
            'referer'        => $referer
        ));
    }

    /**
     * Charge la liste des équipes
     * RQ: on récupère la variable trueLocale qui contient la locale de la requête principale
     * car pour le moment un bug fait que la locale de la sous-requête est la locale par défaut sinon.
     * @see app/Ressources/views/layout.html.twig
     * @return Response
     */
    public function allTeamsAction($trueLocale)
    {
        $em = $this->getDoctrine()->getManager();
        $teams = $em->getRepository('ActResourceBundle:Team')->findAll();

        // Affichage de la vue
        return $this->render('ActResourceBundle:Team:allTeams.html.twig', array(
            'teams' => $teams,
            'trueLocale' => $trueLocale
        ));
    }
}
