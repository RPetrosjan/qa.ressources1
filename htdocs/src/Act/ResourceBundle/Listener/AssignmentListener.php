<?php

namespace Act\ResourceBundle\Listener;

use Act\ResourceBundle\Entity\CommonTask;
use Act\ResourceBundle\Entity\SubTask;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Act\ResourceBundle\Entity\Assignment;
use Act\ResourceBundle\Entity\SimulatedAssignment;

/**
 * Class AssignmentListener
 *
 * Listener on
 *
 * @package Act\ResourceBundle\Listener
 */
class AssignmentListener implements EventSubscriber
{
    private $serializer;

    public function __construct($serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Subscribe for events
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array('onFlush');
    }

    /**
     * Callback for onFlush event
     * @param EventArgs $eventArgs
     */
    public function onFlush(EventArgs $eventArgs)
    {
        $em  = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        $simulation = $em->getRepository('ActResourceBundle:Simulation')->findAll();
        $classMetadata = $em->getClassMetadata('Act\ResourceBundle\Entity\SimulatedAssignment');

        // Check if a simulation exists
        if (!empty($simulation)) {
            // Iterate over entities scheduled for insertion
            foreach ($uow->getScheduledEntityInsertions() as $entity) {
                // Only inspect Assignment entities
                if ($entity instanceof Assignment) {
                    $simAss = $this->createSimulatedAssignment('create', $entity);
                    $em->persist($simAss);

                    $uow->computeChangeSet($classMetadata, $simAss);
                }
            }

            // Iterate over entities scheduled for update
            foreach ($uow->getScheduledEntityUpdates() as $entity) {
                // Only inspect Assignment entities
                if ($entity instanceof Assignment) {
                    $simAss = $this->createSimulatedAssignment('update', $entity, $uow->getEntityChangeSet($entity));
                    $em->persist($simAss);

                    $uow->computeChangeSet($classMetadata, $simAss);
                }
            }

            // Iterate over entities scheduled for deletion
            foreach ($uow->getScheduledEntityDeletions() as $entity) {
                // Only inspect Assignment entities
                if ($entity instanceof Assignment) {
                    $simAss = $this->createSimulatedAssignment('delete', $entity);
                    $em->persist($simAss);

                    $uow->computeChangeSet($classMetadata, $simAss);
                }
            }
        }
    }

    /**
     * Create a new SimulatedAssignment,
     * given an event name and an existing assignment
     * and if it's an update, the changeset of the entity
     *
     * @param string     $event
     * @param Assignment $assignment
     * @param array      $changes
     *
     * @return SimulatedAssignment
     */
    private function createSimulatedAssignment($event, Assignment $assignment, array $changes = array())
    {
        $simAss = new SimulatedAssignment();
        $simAss->setEvent($event);

        // Prepare data array
        $data = array(
          'resource_id'       => $assignment->getResource()->getId(),
          'project_id'        => $assignment->getProject()->getId(),
          'day'               => $assignment->getDay()->format('Y-m-d'),
          'workload_assigned' => $assignment->getWorkload(),
          'comment'           => $assignment->getComment(),
          'created'           => $assignment->getCreated()->format('Y-m-d H:i:s')
        );

        if ($assignment->getCommontask()) { $data['commontask_id'] = $assignment->getCommontask()->getId(); }
        if ($assignment->getSubtask()) { $data['subtask_id']    = $assignment->getSubtask()->getId();    }

        if (count($changes) > 0 && $event == 'update') {
            // When updating an assignment
            // Keep the old values to restore if needed
            foreach ($changes as $key => $value) {
                switch ($key) {
                    case 'commontask':
                        if ($value[0] instanceof CommonTask) {
                            $data['commontask_id'] = $value[0]->getId();
                        }
                        break;
                    case 'subtask':
                        if ($value[0] instanceof SubTask) {
                            $data['subtask_id'] = $value[0]->getId();
                        }
                        break;
                    case 'day':
                        $data['day'] = $value[0]->format('Y-m-d');
                        break;
                    case 'workload_assigned':
                        $data['workload_assigned'] = (float) $value[0];
                    default:
                        $data[$key] = $value[0];
                        break;
                }
            }
        }

        // Serialize data into JSON
        $simAss->setSerialized($this->serializer->serialize($data, 'json'));

        return $simAss;
    }
}
