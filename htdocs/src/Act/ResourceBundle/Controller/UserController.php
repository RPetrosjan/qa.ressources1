<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Application\Sonata\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlleur de gestion des utilisateurs
 * Uniquement disponible aux Administrateurs
 */
class UserController extends Controller
{
    /**
     * Affichage de la page de profil de l'utilisateur connecté
     * @return Response
     */
    public function myProfileAction()
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        $referer = $this->container->get('request')->query->get('referer');
        if($referer == null)
            $referer = $this->generateUrl('act_resource_home');

        // Chargement des projets préférés
        $prefered_projects = $em->getRepository('ActResourceBundle:PreferedProject')->getPreferedProjectsOrdered($user);
        $projects = $em->getRepository('ActResourceBundle:Project')->getProjects();

        // On enlève les projets préférés de la liste des projets
        foreach ($projects as $i => $p) {
            foreach ($prefered_projects as $pp) {
                if ($pp->getProject() && $pp->getProject()->getId() == $p->getId()) {
                    unset($projects[$i]); break;
                }
            }
        }

        return $this->render('ActResourceBundle:User:myprofile.html.twig', array(
            'referer' => $referer,
            'prefered_projects' => $prefered_projects,
            'projects' => $projects,
            'teams' => $em->getRepository('ActResourceBundle:Team')->findAll()
        ));
    }

    /**
     * Sauvegarde les modifications liées à mon profil : email, langue...
     */
    public function saveMyProfileAction()
    {
        $redirect = $this->generateUrl('act_resource_user_own_profile');

        if ($this->container->get('request')->getMethod() == 'POST') {
            $em = $this->getDoctrine()->getManager();
            $user = $this->container->get('security.context')->getToken()->getUser();

            $email = $this->container->get('request')->request->get('email');
            $slackUser = $this->container->get('request')->request->get('slack-user');
            $locale = $this->container->get('request')->request->get('locale');

            if ($email != '') {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $user->setEmail($email);
                } else {
                    $this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans('invalid.email'));
                }
            }

            $user->setSlackUser($slackUser);

            $user->setLocale($locale);
            if ($locale != null) {
                $this->get('session')->set('_locale', $locale);
                $redirect = $this->generateUrl('act_resource_user_own_profile', array('_locale' => $locale));
            }

            $em->persist($user);
            $em->flush();
        }

        return $this->redirect($redirect);
    }

    /**
     * Sauvegarde en ajax des projets préférés de l'utilisateur
     */
    public function savePreferedProjectsAction()
    {
        $request = $this->getRequest();
        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();
        $projectsObjects = array();

        // Vérification AJAX
        if(!$request->isXmlHttpRequest())
            throw $this->createNotFoundException($this->get('translator')->trans('only.available.ajax'));

        // Récupèration des données JSON
        $projects = json_decode($request->request->get('projects'));
        foreach ($projects as $project) {
            $project = $em->getRepository('ActResourceBundle:Project')->find($project);
            if ($project) {
                $projectsObjects[] = $project;
            }
        }

        // On supprime tous les anciens et on sauvegarde les nouveaux
        $preferedProjects = $em->getRepository('ActResourceBundle:PreferedProject')->findBy(array('user' => $user));

        // On supprime les projets préférés précédents qui ne sont plus dans la liste
        foreach ($preferedProjects as $prefProj) {
            $found = false;
            foreach ($projectsObjects as $i => $p) {
                if (!is_array($p) && $prefProj->getProject()->getId() == $p->getId()) {
                    $found = true;
                    $projectsObjects[$i] = array('project' => $p);
                    break;
                }
            }

            if (!$found) {
                $em->remove($prefProj);
            }
        }

        // On ajoute les nouveaux
        foreach ($projectsObjects as $newPrefProj) {
            if (is_array($newPrefProj)) {
                // Update position
                foreach ($preferedProjects as $prefProj) {
                    if ($prefProj->getProject()->getId() == $newPrefProj['project']->getId()) {
                        $em->persist($prefProj);
                    }
                }
            } else {
                $pp = new \Act\ResourceBundle\Entity\PreferedProject();
                $pp->setProject($newPrefProj);
                $pp->setUser($user);
                $em->persist($pp);
            }
        }

        $em->flush();

        $array['success'] = 1;
        $response = new Response(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Charge la liste des projets préférés
     * RQ: on récupère la variable trueLocale qui contient la locale de la requête principale
     * car pour le moment un bug fait que la locale de la sous-requête est la locale par défaut sinon.
     * @see app/Ressources/views/layout.html.twig
     * @return Response
     */
    public function preferedProjectsAction($trueLocale)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();

        $pprojects = $em->getRepository('ActResourceBundle:PreferedProject')->getPreferedProjectsOrdered($user);

        // Affichage de la vue
        return $this->render('ActResourceBundle:User:preferedprojects.html.twig', array(
            'pprojects' => $pprojects,
            'trueLocale' => $trueLocale
        ));
    }

    /**
     * Ajout/Retrait d'un projet préféré depuis le planning projet, en AJAX.
     */
    public function preferedProjectAddAction(\Act\ResourceBundle\Entity\Project $project)
    {
        $request = $this->getRequest();
        $em = $this->getDoctrine()->getManager();
        $user = $this->container->get('security.context')->getToken()->getUser();
        $message = '';
        $added = 0;

        // Vérification AJAX
        if(!$request->isXmlHttpRequest())
            throw $this->createNotFoundException($this->get('translator')->trans('only.available.ajax'));

        // On regarde si le projet préféré existe déjà
        $pproject = $em->getRepository('ActResourceBundle:PreferedProject')->findOneBy(array('user' => $user, 'project' => $project));
        if (!$pproject) {
            // ajout de ce projet préféré
            $pproject = new \Act\ResourceBundle\Entity\PreferedProject();
            $pproject->setUser($user);
            $pproject->setProject($project);
            $em->persist($pproject);
            $added = 1;
        } else {
            // retrait de ce projet préféré
            $em->remove($pproject);
            $added = 0;
        }

        $em->flush();

        $array['success'] = 1;
        $array['project'] = array();
        $array['project']['id'] = $project->getId();
        $array['project']['name'] = $project->getName();
        $array['added'] = $added;
        $response = new Response(json_encode($array));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Sauvegarde les paramètres de l'email prévisionnel de l'utilisateur
     */
    public function savePrevisionalEmailSettingsAction()
    {
        if ($this->container->get('request')->getMethod() == 'POST') {
            $em = $this->getDoctrine()->getManager();
            $user = $this->container->get('security.context')->getToken()->getUser();
            $teams = $this->get('request')->request->get('teams');
            $subscribedTo = array();

            // On retire les précédentes équipes auxquel cet utilisateur à souscrit
            foreach ($user->getPrevisionalTeams() as $prevteam) {
                $user->removePrevisionalTeam($prevteam);
            }

            // On ajoute les nouvelles équipes choisies
            foreach ($teams as $team) {
                $team = $em->getRepository('ActResourceBundle:Team')->find($team);
                if (!$team) {
                    continue;
                } else {
                    $user->addPrevisionalTeam($team);
                    $team->addPrevisionalSubscriber($user);
                    $em->persist($user);
                    $subscribedTo[] = $team->getName();
                }
            }

            $em->flush();
            $this->get('session')->getFlashBag()->add('success', $this->get('translator')->trans('previsional.email.subscribe.for') . ' ' . join(', ', $subscribedTo));
        }

        $referer = $this->container->get('request')->request->get('referer');
        if($referer == null)
            $referer = $this->generateUrl('act_resource_home');

        return $this->redirect($this->generateUrl('act_resource_user_own_profile'));
    }

    /**
     * Generate the page for a user first login where
     * he has to choose his linked resource.
     */
    public function firstLoginAction()
    {
        $request = $this->getRequest();
        $firstLogin = $this->get('act_resource.first_login');

        if ($request->getMethod() == 'POST') {
            $firstLogin->linkResource($request->request->get('resource'));

            return $this->redirect($this->generateUrl('act_resource_home'));
        }

        $availableResources = $firstLogin->getAvailableResources();

        return $this->render('ActResourceBundle:User:firstLogin.html.twig', array(
            'resources' => $availableResources
        ));
    }

    /**
     * Returns a list of user in JSON
     *
     * @param Request $request
     *
     * @return Response
     */
    public function ajaxUserListAction(Request $request)
    {
        $name = $request->get('string');
        if ($name == '') {
            $response = new Response(json_encode(''));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $users = $this->getDoctrine()->getRepository('ApplicationSonataUserBundle:User')->getUsersLike($name);

        $result = array();
        foreach ($users as $user) {
            $result[] = array(
                'id'        => $user->getId(),
                'username'  => $user->getUsername()
            );
        }

        $response = new JsonResponse();
        $response->setData(json_encode($result));

        return $response;
    }
}
