<?php

namespace Act\ResourceBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;



/**
 * RestController
 *
 * Exposes some data via JSON.
 */
class RestController extends ContainerAware
{
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

    /*
     * Created By Ruben 27.10.2017
     * Recuperation des Nom projets et retour son code, chef de project, start et cloture du project
     */
    public function selectprojectAction(Request $request, $nomproject){

        // Initialisation array
        $ArryaReponseObj =  array();
        $ArryaReponseObj['customerName'] = '';
        $ArryaReponseObj['number'] = '';
        $ArryaReponseObj['startDate'] = '';
        $ArryaReponseObj['closingDate'] = '';
        $ArryaReponseObj['externalCode'] = '';
        $ArryaReponseObj['trigramme'] = '';

       /// $nomproject = 'ALE - DEMAND GEN Q1 2017';
        //Url pour recuperation du liste des Projects du Alibeez
        $nomproject = urlencode($nomproject);
        $url = 'https://actency.my.alibeez.com/api/query/sales/contracts?key=5139a3aae1414da19a9c556e88f28def&fields=number,customerName,operationalManagerUsername,closed,startDate,closingDate,sendDate&filter=name%3D%3D'.$nomproject;


        //Recuperation du contenu du URL
        $result = file_get_contents($url, false, stream_context_create(array(
            'http' => array(
                'method'  => 'GET',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
            )
        )));



        //Mettre en Array
        $reponseArrayProject =  json_decode($result,true);

        //Encore fois recuperation projet son code ContractNumber
        $url = 'https://actency.my.alibeez.com/api/query/projects/extract?key=5139a3aae1414da19a9c556e88f28def&fields=name,contractNumber&filter=name%3D%3D'.$nomproject;
        //Recuperation du contenu du URL
        $result = file_get_contents($url, false, stream_context_create(array(
            'http' => array(
                'method'  => 'GET',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
            )
        )));

        //Mettre en Array
        $reponseArrayProject1 =  json_decode($result,true);




        //Si resultat n'est pas vide
        if(sizeof($reponseArrayProject['result'])>0)
        {
            //Mettre les valuers
            $ArryaReponseObj['customerName'] = $reponseArrayProject['result'][0]['customerName'];
            $ArryaReponseObj['number'] = $reponseArrayProject['result'][0]['number'];
            $ArryaReponseObj['startDate'] = $reponseArrayProject['result'][0]['startDate'];
            $ArryaReponseObj['closingDate'] = $reponseArrayProject['result'][0]['closingDate'];
            $ArryaReponseObj['contractNumber'] = $reponseArrayProject1['result'][0]['contractNumber'];

            //Recuperation Societe Name
            $societename = $reponseArrayProject['result'][0]['customerName'];
            //Convert text to url
            $societename = urlencode($societename);
            //Recuperation info du societe
            $urlsociete = 'https://actency.my.alibeez.com/api/query/companies?key=5139a3aae1414da19a9c556e88f28def&fields=name,externalCode&filter=name%3D%3D'.$societename;

            //Recuperation Info client societe
            $result = file_get_contents($urlsociete, false, stream_context_create(array(
                'http' => array(
                    'method'  => 'GET',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                )
            )));
            $reponseArraySociete =  json_decode($result,true);

            //Si resultat n'est pas vide
            if(sizeof($reponseArraySociete['result'])>0)
            {
                //Mettre les valuers
                $ArryaReponseObj['externalCode'] = $reponseArraySociete['result'][0]['externalCode'];
            }

            $operationalManagerUsername = $reponseArrayProject['result'][0]['operationalManagerUsername'];
            //Convert text to url
            $operationalManagerUsername = urlencode($operationalManagerUsername);
            $urluser = 'https://actency.my.alibeez.com/api/query/users?key=5139a3aae1414da19a9c556e88f28def&fields=lastName,firstName,emailPro,emailPerso,extension.trigramme,username&filter=username%3D%3D'.$operationalManagerUsername;
            //Recuperation Info client societe
            $result = file_get_contents($urluser, false, stream_context_create(array(
                'http' => array(
                    'method'  => 'GET',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                )
            )));
            $reponseArrayUser=  json_decode($result,true);

            //Si resultat n'est pas vide
            if(sizeof($reponseArrayUser['result'])>0) {
                //Mettre les valuers
                $ArryaReponseObj['trigramme'] = $reponseArrayUser['result'][0]['extensions']['trigramme'];
                $ArryaReponseObj['lastName'] = $reponseArrayUser['result'][0]['lastName'];
                $ArryaReponseObj['firstName'] = $reponseArrayUser['result'][0]['firstName'];
            }

        }

        return new JsonResponse($ArryaReponseObj);

    }

    /*
     * Created By Ruben 27.10.2017
     * CheckCreationclient
     */

    public function CheckClient($id)
    {
      //  $em = $this->container->get('doctrine')->getManager();
       /// $client = $em->getRepository('ActResourceBundle:Client')->findOneBy(array('id' => $id));
        return $id;

    }
}
