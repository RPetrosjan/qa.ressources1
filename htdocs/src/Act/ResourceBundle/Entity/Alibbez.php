<?php

namespace Act\ResourceBundle\Entity;
use Symfony\Bundle\AsseticBundle\Controller\AsseticController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

//That class created by ruben 25/10/2017

/*
 *  Class Alibbez est sert pour recuperation des donnes du alibeez
 */
class Alibbez
{




    //Recuperation des Project du ALibeez
    public function getAlibbezProjects(){

        //Url pour recuperation du liste des Projects du Alibeez
       $url = 'https://actency.my.alibeez.com/api/query/projects/extract?key=5139a3aae1414da19a9c556e88f28def&fields=name,code,startDay,endDay,contractNumber,customerName,closed,operationalManager,contract.tag.contract-billing-type,contract.tag.contract-BU,operationalManagerUsername&filter=contractNumber%21%3Dnull';


       /// Check if url accessible
        $timeout = 10;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        $result = curl_exec($ch);
        curl_close($ch);

        //Definition Array Projets
        $arrayProjects = array();

        //si le result true donc l'adresse url est accessible
        if($result== true)
        {
            //Recuperation du contenu du URL
            $result = file_get_contents($url, false, stream_context_create(array(
                'http' => array(
                    'method'  => 'GET',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                )
            )));

            //conversation sur Array
            $reponseArray =  json_decode($result,true);

            //Recuperation des elements Name ? code
            foreach ($reponseArray['result'] as $key => $value){
                $arrayProjects[] = $value['name'];
            }

        }

        //Affisher la liste
        return $arrayProjects;
    }
}
