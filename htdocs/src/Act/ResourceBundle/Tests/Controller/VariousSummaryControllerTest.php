<?php

namespace Act\ResourceBundle\Tests\Controller;

use Act\MainBundle\Tests\CustomTestCase;

/**
 * Class VariousSummaryControllerTest
 *
 * Testing the various summary Controller and Service
 *
 */
class VariousSummaryControllerTest extends CustomTestCase
{
    protected $page = null;

    /**
     * Test l'affichage des projets actifs c'est à dire les projets ayant une affectation ou une tache sur la période
     * On testera sur la période du 01/03/2014 au 31/03/2014
     * un seul Projet Actif : le Projet 1 avec 6 affectations pr 6 jours assignés et 0 tâches
     */
    public function testActiveProjects()
    {
        $client = static::createClient();
        $crawler = $this->loadDefaultPage($client);

        $data = array('Projet 1', 6, 0);
        $thisClosure = $this;
        $crawler->filter('#activeProject tr')->reduce(
          function ($node, $i) {
              return ($i == 0); // Get only first line
          }
        )->filter('td')->reduce(
          function ($node, $i) use ($data, $thisClosure) {
              if ($i < 3) { // Skip the last TD with buttons
                  if ($i == 0) {
                      // Project name
                      $name = trim($node->filter('span.project-name')->html());
                      $thisClosure->assertEquals($data[$i], $name);
                  } else {
                      // Data like number of tasks, assignments...
                      $digit = trim($node->filter('span.digit')->html());
                      $thisClosure->assertEquals($data[$i], $digit);
                  }
              }
          }
        );
    }

    /**
     * Test de l'affichages des ressources actives càd les ressources ayant au moins une affectation sur la période
     * On testera la période du 01/03/2014 au 31/03/2014
     * une seule ressource active le Developpeur 3 avec 6 affectations pr un total de 6 jours
     */
    public function testActiveResources()
    {
        $client = static::createClient();
        $crawler = $this->loadDefaultPage($client);

        $data = array('Développeur 3', 6, 6);
        $thisClosure = $this;
        $crawler->filter('#activeResources tr td')->each(
          function ($node, $i) use ($data, $thisClosure) {
              if ($i < 3) { // Skip the last TD with buttons
                  if ($i == 0) {
                      // Resource name
                      $name = trim($node->html());
                      $thisClosure->assertEquals($data[$i], $name);
                  } else {
                      // Data like number of tasks, assignments...
                      $digit = trim($node->filter('span.digit')->html());
                      $thisClosure->assertEquals($data[$i], $digit);
                  }
              }
          }
        );
    }

    /**
     * Test de l'affichage desp rojets actifs mais qui n'ont pas d'affectations ni de taches sur la période donnée
     * On testera la période du 01/03/2014 au 31/03/2014
     * Les projets congés et projet 2 n'ont pas d'effactations pr en mars 2014
     */
    public function testSleepingProjects()
    {
        $client = static::createClient();
        $crawler = $this->loadDefaultPage($client);

        $data = array('Congés', 'Projet 2');
        $thisClosure = $this;
        $crawler->filter('#sleepingProjects tr')->each(
          function ($node, $i) use ($data, $thisClosure) {
              $node->filter('td')->each(
                function ($node, $j) use ($i, $data, $thisClosure) {
                    if ($j == 0) {
                        $name = trim($node->filter('span.project-name')->html());
                        $thisClosure->assertEquals($data[$i], $name);
                    }
                }
              );
          }
        );
    }

    /**
     * Test des ressources en veilles càd les ressources n'ayant pas d'affectations pr la période donnée
     * On testera la période du 01/03/2014 au 31/03/2014
     */
    public function testSleepingResources()
    {
        $client = static::createClient();
        $crawler = $this->loadDefaultPage($client);

        $data = array(
          'Chef de projet fonctionnel 1',
          'Chef de projet fonctionnel 2',
          'Créa 1',
          'Créa 2',
          'Créa 3',
          'Développeur 1',
          'Développeur 2',
          'Développeur 4'
        );
        $thisClosure = $this;
        $crawler->filter('#sleepingResources tr')->each(
          function ($node, $i) use ($data, $thisClosure) {
              $node->filter('td')->each(
                function ($node, $j) use ($i, $data, $thisClosure) {
                    if ($j == 0) {
                        $name = trim($node->html());
                        $thisClosure->assertEquals($data[$i], $name);
                    }
                }
              );
          }
        );
    }

    /**
     * On test lesp rpojets actifs désactivés càd les projets ayant des taches et ou affectations mais qui ont le statut désactivé
     * On testera la période du 01/03/2014 au 31/03/2014
     * il n'y a pas de projets désactivés ac des taches/affectations pr cette periode
     */
    public function testInactiveProjects()
    {
        $client = static::createClient();
        $crawler = $this->loadDefaultPage($client);

        $this->assertEquals(0, $crawler->filter('#inactiveProjects tr td')->count());
    }

    public function testActiveProjectFor()
    {
        $client = static::createClient();
        $crawler = $this->loadPage($client, new \DateTime('2013-12-01'), new \DateTime('2013-12-31'));
        $data = array('Projet 1', 22, 6);
        $thisClosure = $this;
        $crawler->filter('#activeProject tr')->reduce(
          function ($node, $i) {
              return ($i == 0); // Get only first line
          }
        )->filter('td')->reduce(
          function ($node, $i) use ($data, $thisClosure) {
              if ($i < 3) { // Skip the last TD with buttons
                  if ($i == 0) {
                      // Project name
                      $name = trim($node->filter('span.project-name')->html());
                      $thisClosure->assertEquals($data[$i], $name);
                  } else {
                      // Data like number of tasks, assignments...
                      $digit = trim($node->filter('span.digit')->html());
                      $thisClosure->assertEquals($data[$i], $digit);
                  }
              }
          }
        );
    }

    public function testInactiveProjectFor()
    {
        $client = static::createClient();
        $crawler = $this->loadPage($client, new \DateTime('2013-11-25'), new \DateTime('2013-12-25'));
        $data = array('Ancien projet', 1, 0);
        $thisClosure = $this;
        $crawler->filter('#inactiveProjects tr td')->each(
          function ($node, $i) use ($data, $thisClosure) {
              if ($i < 3) {
                  if ($i == 0) {
                      $name = trim($node->filter('span.project-name')->html());
                      $thisClosure->assertEquals($data[$i], $name);
                  } else {
                      $digit = trim($node->filter('span.digit')->html());
                      $thisClosure->assertEquals($data[$i], $digit);
                  }
              }
          }
        );
    }

    /**
     * Helper function to load the various summary page
     *
     * @param Client $client
     * @param \DateTime $start
     * @param \DateTime $end
     *
     * @return Crawler
     */
    private function loadPage($client, \DateTime $start, \DateTime $end)
    {
        $crawler = $client->request('GET', 'fr/admin/summary/various');

        $this->assertCount(1, $crawler->filter('form button[name="submit-period"]'));

        $buttonCrawlerNode = $crawler->selectButton('submit-period');

        $form = $buttonCrawlerNode->form(
          array(
            'start' => $start->format('d/m/Y'),
            'end' => $end->format('d/m/Y')
          )
        );

        return $client->submit($form);
    }

    /**
     * Load the default page for testing
     */
    private function loadDefaultPage($client)
    {
        if ($this->page == null) {
            $this->page = $this->loadPage(
              $client,
              \DateTime::createFromFormat('d/m/Y', '01/03/2014'),
              \DateTime::createFromFormat('d/m/Y', '31/03/2014')
            );
        }

        return $this->page;
    }
}
