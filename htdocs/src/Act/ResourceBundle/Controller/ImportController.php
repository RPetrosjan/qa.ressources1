<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use Act\ResourceBundle\Entity\BankHoliday;
use Act\ResourceBundle\Entity\Location;
use Act\ResourceBundle\Entity\Resource;
use Act\ResourceBundle\Entity\Team;
use Act\ResourceBundle\Entity\TeamProfile;
use Act\ResourceBundle\Entity\Project;

/**
 * ImportController
 *
 * Manage the import of data into the application
 */
class ImportController extends Controller
{
    private $file_path = '/../web/documents/Excel/import.xlsx';
    private $project_dir = '/../web/documents/Excel/Projects/';

    /**
     * Importation des jours fériés
     * @return Response
     */
    public function bankholidaysAction()
    {
        $em = $this->getDoctrine()->getManager();

        // Vérification des droits
        if(!$this->get('security.context')->isGranted('ROLE_ADMIN') )
            throw new AccessDeniedHttpException($this->get('translator')->trans('access.limited.to.admin'));

        // On utilise un Bundle pour ouvrir les fichiers Excel
        $exelObj = $this->get('xls.load_xls2007')->load($this->get('kernel')->getRootDir().$this->file_path);

        // On se place sur l'onglet numéro 4
        $exelObj->setActiveSheetIndex(4);

        $inserted = 0;              // Nombre de jours fériés insérés
        $column = 0;                // Numéro de colone pour le parcours
        $row    = 2;                // Numéro de ligne pour le parcours
        $already_exists = array();  // Jours fériés préexistants
        $not_inserted = array();    // Jours fériés non insérés cause erreur
        $continue = true;           // Condition de fin de la boucle
        $no_date_nb = 0;            // Combientième ligne sans date ? (pour quitter la boucle)

        // Parcours de la feuille Excel
        while ($continue) {
            // On récupère la date du jour férié - 1ere colonne - sa valeur formaté sinon la fonction retourne null
            $date = \DateTime::createFromFormat('m-d-y', $exelObj->getActiveSheet()->getCellByColumnAndRow($column, $row)->getFormattedValue());

            // On récupère le nom du jour férié - 2e colonne - valeur formatté également (sinon bug 08-mai-45 par exemple)
            $name = $exelObj->getActiveSheet()->getCellByColumnAndRow($column+1, $row)->getFormattedValue();

            // Si pas de date on continue à la ligne suivante
            if (!$date) {
                // On passe à la ligne suivante, on ajoute une ligne sans date trouvée
                $row++;
                $no_date_nb++;
                // Si on est déjà à plus de 2 lignes sans date, ça veut dire qu'on a atteint la fin du fichier excel
                if($no_date_nb > 2)
                    $continue = false; // On quitte la boucle au prochain tour
                continue; // Et on lance directement le prochain tour pour quitter tout de suite
            } else {
                // Si on a trouvé une date, on oublie pas de remettre le nombre de ligne sans date parcourues à 0
                $no_date_nb = 0;
            }

            // On vérifie si le jour férié existe déjà en base de donnée
            $bankholiday = $em->getRepository('ActResourceBundle:BankHoliday')->findOneBy(array('name' => $name, 'start' => $date));
            if (!$bankholiday) {
                // Le jour férié n'existe pas encore en BDD, création d'un nouveau jour férié
                $bankholiday = new BankHoliday();
                $bankholiday->setName($name);
                $bankholiday->setStart($date);

                // Ajout des localisations du jour férié - 3e colonne (coché si strasbourg uniquement)
                // NB: On considère que l'inde n'a pas de jours fériés pour le moment !
                if ($exelObj->getActiveSheet()->getCellByColumnAndRow($column+2, $row)->getValue() != null) {
                    // La case est cochée, ce qui veut dire que ce jour férié est uniquement pour Strasbourg
                    // On vérifie que le lieu Strasbourg existe en BDD
                    $location = $em->getRepository('ActResourceBundle:Location')->findOneBy(array('name' => 'Strasbourg'));
                    if ($location) {
                        // On ajoute le lieu à ce jour férié
                        $bankholiday->addLocation($location);
                        $em->persist($bankholiday);
                        // Incrémentation du nombre de jours fériés insérés
                        $inserted++;
                    } else {
                        // Le lieu Strasbourg n'existe pas encore, on n'insère pas le jour férié mais on le notifiera après
                        $not_inserted[] = $bankholiday->__toString();
                    }
                } else {
                    // La case n'est pas cochée, ce qui veut dire qu'on va ajotuer Paris + Strasbourg
                    // On vérifie que les lieux Paris et Strasbourg existent en BDD
                    $locations = $em->getRepository('ActResourceBundle:Location')->findBy(array('name' => array('Strasbourg', 'Paris')));
                    if (count($locations) == 2) {
                        // On ajoutes ces lieux au jour férié
                        foreach ($locations as $location) {
                            $bankholiday->addLocation($location);
                        }
                        $em->persist($bankholiday);
                        // Incrémentation du nombre de jours fériés insérés
                        $inserted++;
                    } else {
                        // Un des deux lieux n'existe pas,  on n'insère pas le jour férié mais on le notifiera après
                        $not_inserted[] = $bankholiday->__toString();
                    }
                }
            } else {
                // Le jour férié existe déjà en BDD, on le notifiera après
                $already_exists[] = $bankholiday->__toString();
            }

            // On passe à la prochaine ligne du fichier Excel
            $row++;
        }

        try {
            // Sauvegarde de tous les jours fériés persistés
            $em->flush();

            // Notification du nombre de jours fériés importés
            if($inserted > 0)
                $this->get('session')->getFlashBag()->add('success', $inserted.' jour(s) férié(s) inséré(s)');

            // Notification du nombre de jours fériés déjà existant et donc non importés
            if(count($already_exists) > 0)
                $this->get('session')->getFlashBag()->add('warning', count($already_exists).' jours fériés n\'ont pas été insérés car ils existent déjà : '.  implode(', ', $already_exists));

            // Notification du nombre de jours fériés dont le ou les lieux n'existaient pas donc non importés
            if(count($not_inserted) > 0)
                $this->get('session')->getFlashBag()->add('error', count($not_inserted).' jours fériés n\'ont pas été insérés car leur lieu n\'existe pas : '.  implode(', ', $not_inserted));

        } catch (\Exception $e) {
            // Dans le cas d'un echec même avec toutes ces vérification, tout l'import est annulé (transaction de Doctrine)
            $this->get('session')->getFlashBag()->add('error', $e->getMessage().' - Import annulé');
        }

        // Redirection
        return $this->redirect($this->generateUrl('act_resource_bankholiday'));
    }

    /**
     * Importation des lieux
     * @return Response
     */
    public function locationsAction()
    {
        // Vérification des droits
        if(!$this->get('security.context')->isGranted('ROLE_ADMIN') )
            throw new AccessDeniedHttpException($this->get('translator')->trans('access.limited.to.admin'));

        // Utilisation Bundle spécial
        $exelObj = $this->get('xls.load_xls2007')->load($this->get('kernel')->getRootDir().$this->file_path);
        $em = $this->getDoctrine()->getManager();

        // 3e onglet de la feuille excel
        $exelObj->setActiveSheetIndex(3);

        $inserted = 0;              // Nombre de lieux insérés
        $column = 0;                // Numéro Colonne - parcours
        $row    = 2;                // Numéro ligne - parcours
        $already_exists = array();  // Lieux déjà existants en BDD

        // Parcours de la feuille Excel tant qu'on trouve une valeur pour le nom du lieu : 1ere colonne
        while (($location_name = $exelObj->getActiveSheet()->getCellByColumnAndRow($column, $row)->getValue()) != null) {
            // On teste son existence en BDD
            $location = $em->getRepository('ActResourceBundle:Location')->findOneBy(array('name' => $location_name));
            if (!$location) {
                // N'existe pas encore : création de l'objet
                $location = new Location();
                $location->setName($location_name);
                $em->persist($location);
                // Incrémentation du nbr de lieux insérés
                $inserted++;
            } else {
                // Pour notif plus tard, lieux déjà existants
                $already_exists[] = $location->__toString();
            }

            // Ligne suivante
            $row++;
        }

        try {
            // Sauvegarde de tous les lieux
            $em->flush();

            // Notification nombre d'insertion
            if($inserted > 0)
                $this->get('session')->getFlashBag()->add('success', $inserted.' lieu(x) inséré(s)');

            // Notification nombre de lieux déjà existants
            if(count($already_exists) > 0)
                $this->get('session')->getFlashBag()->add('warning', count($already_exists).' lieux n\'ont pas été insérés car ils existent déjà : '.  implode(', ', $already_exists));

        } catch (\Exception $e) {
            // Si problème malgré tout, on annule tout l'import (merci aux transactions Doctrine)
            $this->get('session')->getFlashBag()->add('error', $e->getMessage().' - Import annulé');
        }

        // Redirection
        return $this->redirect($this->generateUrl('act_resource_location'));
    }

    /**
     * Importation des ressources
     * @return Response
     */
    public function resourcesAction()
    {
        if(!$this->get('security.context')->isGranted('ROLE_ADMIN') )
            throw new AccessDeniedHttpException($this->get('translator')->trans('access.limited.to.admin'));

        $exelObj = $this->get('xls.load_xls2007')->load($this->get('kernel')->getRootDir().$this->file_path);
        $em = $this->getDoctrine()->getManager();
        $exelObj->setActiveSheetIndex(0);

        $inserted = 0;
        $column = 0;
        $row    = 2;
        $already_exists = array();
        $not_inserted = array();
        while (($ress_name = $exelObj->getActiveSheet()->getCellByColumnAndRow($column, $row)->getValue()) != null) {
            // On teste son existence en BDD
            $resource = $em->getRepository('ActResourceBundle:Resource')->findOneBy(array('name' => $ress_name));
            if (!$resource) {
                // Création d'une nouvelle resource
                $resource = new Resource();
                // Nom
                $resource->setName($ress_name);
                // Code
                $resource->setNameShort($exelObj->getActiveSheet()->getCellByColumnAndRow($column+1, $row)->getValue());
                // Date de début
                $start = \DateTime::createFromFormat('m-d-y', $exelObj->getActiveSheet()->getCellByColumnAndRow($column+7, $row)->getFormattedValue());
                $resource->setStart($start);
                // Date de fin
                if (($end = $exelObj->getActiveSheet()->getCellByColumnAndRow($column+8, $row)->getFormattedValue()) != null) {
                    $end = \DateTime::createFromFormat('m-d-y', $end);
                    $resource->setEnd($end);
                }
                // Nombre jours travaillés par semaine
                $resource->setDaysPerWeek($exelObj->getActiveSheet()->getCellByColumnAndRow($column+10, $row)->getValue());
                // Equipe et Localisation
                $location = $em->getRepository('ActResourceBundle:Location')->findOneBy(array('name' => $exelObj->getActiveSheet()->getCellByColumnAndRow($column+4, $row)->getValue()));
                $team = $em->getRepository('ActResourceBundle:Team')->findOneBy(array('name' => $exelObj->getActiveSheet()->getCellByColumnAndRow($column+3, $row)->getValue()));
                if ($team && $location) {
                    $resource->setTeam($team);
                    $resource->setLocation($location);
                    // Persistance activée
                    $em->persist($resource);
                    $inserted++;
                } else {
                    $not_inserted[] = $resource->__toString();
                }
            } else {
                $already_exists[] = $resource->__toString();
            }

            $row++;
        }

        try {
            // Sauvegarde
            $em->flush();

            if($inserted > 0)
                $this->get('session')->getFlashBag()->add('success', $inserted.' ressources(s) insérée(s)');

            if(count($already_exists) > 0)
                $this->get('session')->getFlashBag()->add('warning', count($already_exists).' ressources n\'ont pas été insérées car elles existent déjà : '.  implode(', ', $already_exists));

            if(count($not_inserted) > 0)
                $this->get('session')->getFlashBag()->add('error', count($not_inserted).' ressources n\'ont pas été insérées car leur équipe/lieu n\'existe pas : '.  implode(', ', $not_inserted));

        } catch (\Exception $e) {
            // Echec si existe déjà
            $this->get('session')->getFlashBag()->add('error', $e->getMessage().' - Import annulé');
        }

        return $this->redirect($this->generateUrl('act_resource_resource'));
    }

    /**
     * Importation des différentes équipes et profils
     */
    public function teamsAction()
    {
        if(!$this->get('security.context')->isGranted('ROLE_ADMIN') )
            throw new AccessDeniedHttpException($this->get('translator')->trans('access.limited.to.admin'));

        $exelObj = $this->get('xls.load_xls2007')->load($this->get('kernel')->getRootDir().$this->file_path);
        $em = $this->getDoctrine()->getManager();
        $exelObj->setActiveSheetIndex(1);

        $inserted = 0; $inserted_profiles = 0;
        $column = 0;
        $row    = 2;
        $already_exists = array();
        while (($team_name = $exelObj->getActiveSheet()->getCellByColumnAndRow($column, $row)->getValue()) != null) {
            // On teste son existence en BDD
            $team = $em->getRepository('ActResourceBundle:Team')->findOneBy(array('name' => $team_name));
            if (!$team) {
                // Création nouvelle équipe
                $team = new Team();
                $team->setColor($exelObj->getActiveSheet()->getCellByColumnAndRow($column + 1, $row)->getValue());
                $team->setName($team_name);
                // Persistance activée
                $em->persist($team);
                $em->flush();
                $inserted++;
            } else {
                $already_exists[] = $team->__toString();
            }

            // Création des profils
            $profiles = explode(' ,, ', $exelObj->getActiveSheet()->getCellByColumnAndRow($column + 2, $row)->getValue());
            foreach ($profiles as $profile) {
                if ($profile != '') {
                    // On teste son existence en BDD
                    $teamprofile = $em->getRepository('ActResourceBundle:TeamProfile')->findOneBy(array('name' => $profile, 'team' => $team));
                    if (!$teamprofile) {
                        // Création nouveau profil
                        $teamprofile = new TeamProfile();
                        $teamprofile->setTeam($team);
                        $teamprofile->setName($profile);

                        // Persistance activée
                        $em->persist($teamprofile);
                        $inserted_profiles++;
                    }
                }
            }

            $row++;
        }

        try {
            // Sauvegarde
            $em->flush();

            if($inserted > 0 || $inserted_profiles > 0)
                $this->get('session')->getFlashBag()->add('success', $inserted.' équipe(s) insérée(s), '.$inserted_profiles.' profil(s) inséré(s)');

            if(count($already_exists) > 0)
                $this->get('session')->getFlashBag()->add('warning', count($already_exists).' équipes n\'ont pas été insérées car elles existent déjà : '.  implode(', ', $already_exists));

        } catch (\Exception $e) {
            $this->get('session')->getFlashBag()->add('error', $e->getMessage().' - Import annulé');
        }

        return $this->redirect($this->generateUrl('act_resource_team'));
    }

    /**
     * Réalise la migration depuis le fichier de suivi excel ancien, vers le système actuel
     * @param  string   $onglet - le nom de l'onglet
     * @return Response
     */
    public function migrateAction($onglet, $projectName)
    {
        set_time_limit(0);

        $em = $this->getDoctrine()->getManager();
        $project = $em->getRepository('ActResourceBundle:Project')->findOneBy(array('name' => $projectName));
        if(!$project) throw $this->createNotFoundException($this->get('translator')->trans('unable.to.find.project'));

        $exelObj = $this->get('xls.load_xls2007')->setLoadSheetsOnly(array($onglet, 'Config'))->load('documents/Excel/migrate.xlsx');

        $row = 1;
        $column = 0;
        $continue = true;

        $projectName = $onglet;                                                     // Nom du projet
        $firstRessRow = null;                                                       // 1ere ligne du tableau des affectations
        $lastRessRow = null;                                                        // dernière ligne du tableau des affectations
        $resources = array();                                                      // Tableau associatif RessourceShort => ligne
        $startDate = \DateTime::createFromFormat('d/m/Y', '17/10/2011')->setTime(0,0,0); // Date de début
        $endDate = \DateTime::createFromFormat('d/m/Y', '01/02/2013')->setTime(0,0,0);   // Date de fin
        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate);  // période couverte
        $totalAffAdded = 0;

        // 1. Première ligne des ressources
        $firstRessRow = 20;
        $row = $firstRessRow;

        // 2. On mémorise les ressources et leur ligne
        $continue = true; $unfound = 0;
        while ($continue) {
            $tmp = $exelObj->getActiveSheet()->getCellByColumnAndRow($column, $row)->getCalculatedValue();
            $resource = $em->getRepository('ActResourceBundle:Resource')->findOneBy(array('nameShort' => $tmp));
            if (!$resource) {
                $unfound++; $row++;
                if($unfound > 10) $continue = false;
            } else {
                $resources[$row] = $resource;
                $row++;
                $lastRessRow = $row;
            }
        }

        // 3. On extrait pour chaque ressource les affectations
        foreach ($resources as $rrow => $resource) {
            $row = $rrow; $column = 1;
            foreach ($period as $date) {
                if ($date->format('N') < 6) {
                    $tmp = $exelObj->getActiveSheet()->getCellByColumnAndRow($column, $row)->getValue();
                    if (is_float($tmp) && $tmp > 0) {
                        $assignment = new \Act\ResourceBundle\Entity\Assignment();
                        $assignment->setDay($date);
                        $assignment->setProject($project);
                        $assignment->setResource($resource);
                        $assignment->setWorkload($tmp);
                        $em->persist($assignment);
                        $totalAffAdded++;
                    }
                    $column++;
                }
            }
        }

        // 4. On enregistre le tout en BDD
        $em->flush();

        $this->get('session')->getFlashBag()->set('success', $totalAffAdded.' affectations ajoutées');

        return $this->redirect($this->generateUrl('act_resource_home'));
    }
}
