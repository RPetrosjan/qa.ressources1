<?php

namespace Act\ResourceBundle\Controller;


use Act\ResourceBundle\Entity\Location;
use Act\ResourceBundle\Entity\Team;
use Doctrine\DBAL\Types\DecimalType;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/* Ajoutee par Ruben 06/10/2017 */
use Act\ResourceBundle\Entity\Resource;
use Act\ResourceBundle\Entity\Project;
use Act\ResourceBundle\Entity\Client;
use Symfony\Component\HttpFoundation\Response;



/**
 * RestController
 *
 * Exposes some data via JSON.
 */
class RestController extends ContainerAware
{

    public function addProjectRubenAction($dataJson)
    {
        $ArrayJson = json_decode($dataJson, true);

        //Recuperation l'acces sur Database Secure Symfony
        $em = $this->container->get('doctrine')->getManager();

        //Projet Name
        $projectName = $ArrayJson['projectName'];
        //Project Name Short
        $projectshortname = $ArrayJson['projectshortname'];
        //Start date de Prject
        $startDateProject = new \DateTime($ArrayJson['startDateProject']);
        //End Date de prject
        $endDateProject   = new \DateTime($ArrayJson['endDateProject']);
        // Project Active?
        $activeProject = $ArrayJson['activeProject'];
        //Avant-vente > 70%
        $is_presale_gt70 = $ArrayJson['is_presale_gt70'];
        //Avant-vente < 70%
        $is_presale_lt70 = $ArrayJson['is_presale_lt70'];
        //Projet signé
        $signedProject = $ArrayJson['signedProject'];
        //Projet congés
        $congesProject = $ArrayJson['congesProject'];
        //Projet interne
        $interneProject = $ArrayJson['interneProject'];
        //Projet de R&D
        $is_research = $ArrayJson['is_research'];

        //client
        $nameclient = $ArrayJson['nameclient'];
        //client short name
        $nameclientshort = $ArrayJson['nameclientshort'];
        // contact name
        $contactnameclient = $ArrayJson['contactnameclient'];

        //Chef de Prject
        $nameshortresourcee = $ArrayJson['nameshortresourcee'];
        // Chef de projet Name
        $nameresourcee = $ArrayJson['nameresourcee'];
        //Date Start Ressource
        $startDateresourcee = new \DateTime($ArrayJson['startDateresourcee']);
        // Date End Ressources
        $endDateRessources   = new \DateTime($ArrayJson['endDateRessources']);
        //Days par Semaine travail
        $dayperweeksRessources = $ArrayJson['dayperweeksRessources'];
        //Team Ressource
        $team = $em->getRepository('ActResourceBundle:Team')->findOneBy(array('id' => 10));

        //Agence du Actency
        $LocationAgence = $ArrayJson['LocationAgence'];


        $location = $em->getRepository('ActResourceBundle:Location')->findOneBy(array('name' => $LocationAgence));
        if(!$location){
            $location = new Location();
            $location->setName($LocationAgence);
            $em->persist($location);
            $em->flush();
        }


        //Chercher RH sur son Name chort
        $resource = $em->getRepository('ActResourceBundle:Resource')->findOneBy(array('nameShort' => $nameshortresourcee));
        //Si l'existe pas on cree
        if(!$resource)
        {
            $resource = new Resource();
            $resource->setName($nameresourcee);
            $resource->setNameShort($nameshortresourcee);
            $resource->setStart($startDateresourcee);
            $resource->setEnd($endDateRessources);
            $resource->setDaysPerWeek($dayperweeksRessources);
            $resource->setLocation($location);
            $resource->setTeam($team);
            $em->persist($resource);
            $em->flush();
        }


        //Cherche client par son Name
        $client =  $em->getRepository('ActResourceBundle:Client')->findOneBy(array('name' => $nameclient));

        //Si l'existe pas on cree
         if(!$client) {

                $client = new Client();
                $client ->setName($nameclient);
                $client ->setNameShort($nameclientshort);
                $client ->setContactName($contactnameclient);
                $em->persist($client);
                $em->flush();
         }

       // On cherhce project si l'existe
       $project = $em->getRepository('ActResourceBundle:Project')->findOneBy(array('name' => $projectName));

            if (!$project) {
                // If no project found, create a new one
                $project = new Project();
            }

            //ajout nom de la project
            $project->setName($projectName);
            //Ajoourt Code de Project
            $project->setNameShort($projectshortname);
            //Ajout Projet intern externrr
            $project->setTypeHoliday(true);
            //Ajout Data start du Project
            $project->setStart($startDateProject);
            //ajout Data end du Project
            $project->setEnd($endDateProject);
            //si le prject est active
            $project->setActive($activeProject);
            // Project >70%
            $project->setTypePresaleGT70($is_presale_gt70);
            // Project <70%
            $project->setTypePresaleLT70($is_presale_lt70);
            // Signed
            $project->setTypeSigned($signedProject);
            // Research
            $project->setTypeResearch($is_research);

            //Ajout chef de project
            $project->setCpf($resource);   // Défini le chef de projet fonctionnel
            //Ajout client project
            $project->setClient($client);
            //Preparation et Ajout
            $em->persist($project);
            $em->flush();


        $response = new Response(json_encode($projectName));
        $response->headers->set('Content-Type', 'application/json');

        return $response;

    }


    /**
     * Select query action.
     *
     * /!\ DISCLAIMER /!\
     *
     * Asked by Ruben for Google Docs integration.
     * If any security problems occurs, it's not my fault ;)
     *
     * /!\ THIS MUST BE EXECUTED USING A SPECIFIC USER RESTRICTED TO SELECT CLAUSE ONLY /!\
     *
     * @param Request $request
     * @param string $token
     *   The access checking token.
     * @param string $query
     *   The base64 SQL SELECT query.
     *
     * @return JsonResponse
     *   The JSON data array.
     *
     * @throws \Exception
     *   If any errors occurs.
     */
    public function selectAction(Request $request, $token, $query)
    {
        if($token == 'addProjectRuben')
        {

            $query = '{
                    "key":"a3dcb3fe153cc31afa2e700a4cc3c19e",
                    "projectName":"RubenPeoject2",
                    "projectshortname":"RUTDE",
                    "startDateProject":"2017-05-05 00:00:00",
                    "endDateProject":"2017-05-05 00:00:00",
                    "activeProject":true,
                    "is_presale_gt70":false,
                    "is_presale_lt70":false,
                    "signedProject":true,
                    "congesProject":false,
                    "interneProject":false,
                    "is_research":false,
                    "nameclient":"GEPART",
                    "nameclientshort":"GEPART",
                    "contactnameclient":"",
                    "nameshortresourcee":"ATO",
                    "nameresourcee":"Arnold Toti",
                    "startDateresourcee":"2017-05-05 00:00:00",
                    "endDateRessources":"2018-10-21 00:00:00",
                    "dayperweeksRessources":5,
                    "LocationAgence":"Strasbourg"
                }';

             $ArrayJson = json_decode($query, true);
             if(md5(date("m-d-Y").'ruben') == $ArrayJson['key']){
                 $data = array('status' => 200, 'succes' => 'Valid security token.');
             }
             else{
                 $data = array('status' => 500, 'error' => 'Invalid security token.');
             }

           /// return $this->addProjectRubenAction($query);
            return new JsonResponse($data);
        }
        // Generate the Act&Ressources side token for checking.
        $checkToken = sha1(date('Y:m:d').$this->container->getParameter('restricted_secret'));
        if ($token === $checkToken) {
            // Token okay, let's validate query.
            if (false !== ($query = base64_decode($query, true))) {
                // Make sure it's a SELECT query.
                // It's a basic check, security must be enforced using a specific database user which
                // won't be allowed to execute queries other than select ones.
                $connection = $this->container->get('doctrine')->getConnection('restricted');

                try {
                    $results = $connection->query($query)->fetchAll();
                    $data = array('status' => 200, 'data' => $results);
                } catch (\Exception $e) {
                    // Error with query.
                    $data = array('status' => 500, 'error' => 'Invalid query : '.$e->getMessage());
                }
            } else {
                // Error with base64 encoded query.
                $data = array('status' => 500, 'error' => 'Invalid query.');
            }
        } else {
            // Error with token !
            $data = array('status' => 500, 'error' => 'Invalid security token.');
            $this->container->get('logger')->info('Valid token is : '.$checkToken);
        }

        return new JsonResponse($data);
    }


}
