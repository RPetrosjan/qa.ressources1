<?php

namespace Act\ResourceBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Act\ResourceBundle\Entity\Assignment;

/**
 * LoadAssignmentData
 *
 * Loads initial dataset of Assignments
 *
 */
class LoadAssignmentData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $functions = array('assignmentProvider', 'outOfTaskProvider', 'yearSummaryProvider');

        foreach ($functions as $func) {
          foreach ($this->$func() as $data) {
              $assignment = new Assignment();
              $assignment->setDay($data['day']);
              $assignment->setWorkload($data['workload']);
              $assignment->setProject($data['project']);
              $assignment->setResource($data['resource']);
              if (isset($data['commontask'])) {
                  $assignment->setCommontask($data['commontask']);
              }
              if (isset($data['subtask'])) {
                  $assignment->setSubtask($data['subtask']);
              }
              $manager->persist($assignment);
          }
        }

        $manager->flush();
    }

    /**
     * Initial data to test assignments and tasks
     * @return Array
     */
    private function outOfTaskProvider()
    {
        return array(
            // In task dates, with common and subtask
            array(
                'workload' => 0.5,
                'day' => \DateTime::createFromFormat('j-m-Y', '02-12-2013'),
                'resource' => $this->getReference('resource-dev4'),
                'project' => $this->getReference('project-1'),
                'commontask' => $this->getReference('common-1'),
                'subtask' => $this->getReference('sub-1')
            ),
            // In task dates, with common only
            array(
                'workload' => 0.5,
                'day' => \DateTime::createFromFormat('j-m-Y', '03-12-2013'),
                'resource' => $this->getReference('resource-dev4'),
                'project' => $this->getReference('project-1'),
                'commontask' => $this->getReference('common-1')
            ),
            // Out of subtask dates
            array(
                'workload' => 0.5,
                'day' => \DateTime::createFromFormat('j-m-Y', '04-12-2013'),
                'resource' => $this->getReference('resource-dev4'),
                'project' => $this->getReference('project-1'),
                'commontask' => $this->getReference('common-1'),
                'subtask' => $this->getReference('sub-2')
            ),
            // Out of commontask dates
            array(
                'workload' => 0.5,
                'day' => \DateTime::createFromFormat('j-m-Y', '09-12-2013'),
                'resource' => $this->getReference('resource-dev4'),
                'project' => $this->getReference('project-1'),
                'commontask' => $this->getReference('common-1')
            ),
            // Without tasks
            array(
                'workload' => 0.5,
                'day' => \DateTime::createFromFormat('j-m-Y', '06-12-2013'),
                'resource' => $this->getReference('resource-dev4'),
                'project' => $this->getReference('project-1')
            )
        );
    }

    /**
     * Initial data to test assignment's year summary
     * @return Array
     */
    private function yearSummaryProvider()
    {
        return array(
            // DEV3 : W06 2014 : -2
            array(
                'workload' => 0.5,
                'day' => \DateTime::createFromFormat('j-m-Y', '03-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 0.5,
                'day' => \DateTime::createFromFormat('j-m-Y', '03-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-2')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '04-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-conges')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '05-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '06-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '06-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-2')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '07-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-2')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '07-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            // DEV3 : W07 2014 : -0.1
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '10-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '11-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '12-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-2')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '13-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-2')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '14-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-2')
            ),
            array(
                'workload' => 0.1,
                'day' => \DateTime::createFromFormat('j-m-Y', '14-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-conges')
            ),
            // DEV3 : W08 2014 : 0
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '17-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '18-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '19-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '20-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '21-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            // DEV3 : W09 2014 : 1
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '24-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '25-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '26-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '27-02-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            // DEV3 : W10 2014 : 2
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '03-03-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '04-03-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '07-03-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            // DEV3 : W11 2014 : 3
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '12-03-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '13-03-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            // DEV3 : W12 2014 : 4
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '18-03-2014'),
                'resource' => $this->getReference('resource-dev3'),
                'project' => $this->getReference('project-1')
            ),
            // DEV4 : W01 2014 : -0.5
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '01-01-2014'),
                'resource' => $this->getReference('resource-dev4'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '02-01-2014'),
                'resource' => $this->getReference('resource-dev4'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '03-01-2014'),
                'resource' => $this->getReference('resource-dev4'),
                'project' => $this->getReference('project-1')
            ),
            // DEV4 : W01 2014 : 0
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '07-01-2014'),
                'resource' => $this->getReference('resource-dev4'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '08-01-2014'),
                'resource' => $this->getReference('resource-dev4'),
                'project' => $this->getReference('project-1')
            ),
            array(
                'workload' => 0.5,
                'day' => \DateTime::createFromFormat('j-m-Y', '09-01-2014'),
                'resource' => $this->getReference('resource-dev4'),
                'project' => $this->getReference('project-2')
            ),
            // DEV1 : W06 2014 : 4 - projet désactivé !
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '03-02-2014'),
                'resource' => $this->getReference('resource-dev1'),
                'project' => $this->getReference('project-disabled')
            ),
            array(
                'workload' => 1,
                'day' => \DateTime::createFromFormat('j-m-Y', '03-02-2014'),
                'resource' => $this->getReference('resource-dev1'),
                'project' => $this->getReference('project-1')
            ),
        );
    }

    private function assignmentProvider()
    {
        $assignments = array(
          // 1 Semaine de congés pour Dev1 entre le 02/01/2014 et le 08/01/2014
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '02-01-2014'),
            'resource' => $this->getReference('resource-dev1'),
            'project' => $this->getReference('project-conges')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '03-01-2014'),
            'resource' => $this->getReference('resource-dev1'),
            'project' => $this->getReference('project-conges')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '06-01-2014'),
            'resource' => $this->getReference('resource-dev1'),
            'project' => $this->getReference('project-conges')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '07-01-2014'),
            'resource' => $this->getReference('resource-dev1'),
            'project' => $this->getReference('project-conges')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '08-01-2014'),
            'resource' => $this->getReference('resource-dev1'),
            'project' => $this->getReference('project-conges')
          ),

          // Erreur d'affectation pour Dev2 le 02/12/2013 car indisponible
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '02-12-2013'),
            'resource' => $this->getReference('resource-dev2'),
            'project' => $this->getReference('project-1')
          ),

          // Erreur d'affectation pour Dev3 le 01/11/2013 car jour férié
          array(
            'workload' => 0.5,
            'day' => \DateTime::createFromFormat('j-m-Y', '01-11-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-2')
          ),

          // Tous les cas de couleurs pour les affectations sur le planning
          array(
            'workload' => 0.25,
            'day' => \DateTime::createFromFormat('j-m-Y', '02-12-2013'),
            'resource' => $this->getReference('resource-dev1'),
            'project' => $this->getReference('project-1')
          ),
          array(
            'workload' => 0.5,
            'day' => \DateTime::createFromFormat('j-m-Y', '03-12-2013'),
            'resource' => $this->getReference('resource-dev1'),
            'project' => $this->getReference('project-1')
          ),
          array(
            'workload' => 0.75,
            'day' => \DateTime::createFromFormat('j-m-Y', '04-12-2013'),
            'resource' => $this->getReference('resource-dev1'),
            'project' => $this->getReference('project-1')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '05-12-2013'),
            'resource' => $this->getReference('resource-dev1'),
            'project' => $this->getReference('project-1')
          ),
          array(
            'workload' => 1.5,
            'day' => \DateTime::createFromFormat('j-m-Y', '06-12-2013'),
            'resource' => $this->getReference('resource-dev1'),
            'project' => $this->getReference('project-1')
          ),
          array(
            'workload' => 2,
            'day' => \DateTime::createFromFormat('j-m-Y', '09-12-2013'),
            'resource' => $this->getReference('resource-dev1'),
            'project' => $this->getReference('project-1')
          ),
          // Erreur d'affectations si weekworkload > daysperweek
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '16-12-2013'),
            'resource' => $this->getReference('resource-dev4'),
            'project' => $this->getReference('project-1')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '17-12-2013'),
            'resource' => $this->getReference('resource-dev4'),
            'project' => $this->getReference('project-1')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '18-12-2013'),
            'resource' => $this->getReference('resource-dev4'),
            'project' => $this->getReference('project-1')
          ),

          /**
           * Sur le planning projet, cas où le highlight se fait suite à
           * des affectations sur des projets différents.
           */

          // Coloration: high
          // Dev3 : affectation le 02/12/2013 sur le projet 1
          array(
            'workload' => 0.2,
            'day' => \DateTime::createFromFormat('j-m-Y', '02-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-1')
          ),

          // Dev3 : affectation le 02/12/2013 sur le projet 2
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '02-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-2')
          ),

          // Coloration : low
          // Dev3 : affectation le 03/12/2013 sur le projet 1
          array(
            'workload' => 0.2,
            'day' => \DateTime::createFromFormat('j-m-Y', '03-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-1')
          ),

          // Dev3 : affectation le 03/12/2013 sur le projet 2
          array(
            'workload' => 0.25,
            'day' => \DateTime::createFromFormat('j-m-Y', '03-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-2')
          ),

          // Coloration : ok
          // Dev3 : affectation le 04/12/2013 sur le projet 1
          array(
            'workload' => 0.5,
            'day' => \DateTime::createFromFormat('j-m-Y', '04-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-1')
          ),

          // Dev3 : affectation le 04/12/2013 sur le projet 2
          array(
            'workload' => 0.5,
            'day' => \DateTime::createFromFormat('j-m-Y', '04-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-2')
          ),

          // Coloration : ok (en conflit avec un projet désactivé !)
          // Dev3 : affectation le 05/12/2013 sur le projet 1
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '05-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-1')
          ),

          // Dev3 : affectation le 05/12/2013 sur le projet 3
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '05-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-disabled')
          ),
          // Erreur si weekworkload > daysperweek - avec projets différents
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '16-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-1')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '17-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-1')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '18-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-1')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '16-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-2')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '17-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-2')
          ),
          array(
            'workload' => 1,
            'day' => \DateTime::createFromFormat('j-m-Y', '18-12-2013'),
            'resource' => $this->getReference('resource-dev3'),
            'project' => $this->getReference('project-2')
          ),

          /**
           * Fin highlight planning projet - projets différents
           */

        );

        return $assignments;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 7;
    }
}
