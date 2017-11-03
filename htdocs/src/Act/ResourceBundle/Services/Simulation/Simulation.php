<?php

namespace Act\ResourceBundle\Services\Simulation;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\SecurityContext;
use Act\ResourceBundle\Entity\Assignment;

/**
 * Class Simulation
 *
 * This service contains code to deal with simulation
 * commit and rollback.
 *
 */
class Simulation
{
    private $session;
    private $em;
    private $sc;
    private $translator;

    public function __construct(Session $session, EntityManager $em, SecurityContext $sc, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->em = $em;
        $this->sc = $sc;
        $this->translator = $translator;
    }

    /**
     * Commit an active simulation.
     * Commit means that we just keep every changed done during the simulation.
     *
     * @throws Exception if no simulation exists
     */
    public function commit()
    {
        $this->session->getFlashBag()->clear();

        // Just delete the simulation and simulated assignments
        $simulation = $this->em->getRepository('ActResourceBundle:Simulation')->findAll();
        if (count($simulation) > 0) {
            if ($this->sc->getToken() != null && ($this->sc->getToken()->getUser()->getId() == $simulation[0]->getUser()->getId())) {
                $this->em->beginTransaction();

                try {
                    $simulatedAssignments = $this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findAll();

                    $nbAdd = count($this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findBy(array('event' => 'create')));
                    $nbEdit = count($this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findBy(array('event' => 'update')));
                    $nbDelete = count($this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findBy(array('event' => 'delete')));

                    // Remove all simulations
                    foreach ($simulation as $sim) {
                        $this->em->remove($sim);
                    }

                    // Remove all simulated assignments
                    foreach ($simulatedAssignments as $sim) {
                        $this->em->remove($sim);
                    }

                    $this->em->flush();
                    $this->em->commit();

                    $this->session->getFlashBag()->add(
                        'info',
                        $this->translator->trans('simulation.end.commit') . ' : ' . $nbAdd . ' ' . $this->translator->trans('added.assignments') . ', ' . $nbEdit . ' ' .
                        $this->translator->trans('updated.assignments') . ', ' . $nbDelete . ' ' . $this->translator->trans('deleted.assignments')
                    );

                } catch (\Exception $e) {
                    // If any problem, rollback all changes
                    $this->em->rollback();
                    throw $e;
                }

            } else {
                throw new \Exception('Not allowed to commit simulations from someone else');
            }
        } else {
            throw new \Exception('No active simulation found');
        }
    }

    /**
     * Rollback an active simulation.
     * Rollback means that we have to cancel all modifications done since
     * the beginning of the simulation.
     *
     * @throws Exception if no simulation exists, or a problem occurs
     */
    public function rollback()
    {
        $this->session->getFlashBag()->clear();

        $simulation = $this->em->getRepository('ActResourceBundle:Simulation')->findAll();
        if (count($simulation) > 0) {
            if ($this->sc->getToken() != null && ($this->sc->getToken()->getUser()->getId() == $simulation[0]->getUser()->getId())) {
                $this->em->beginTransaction();

                try {
                    // Firstly remove the simulation, to disable the event listener
                    $this->em->remove($simulation[0]);
                    $this->em->flush($simulation[0]);

                    $nbAdd = 0;
                    $nbEdit = 0;
                    $nbDelete = 0;
                    $simulatedAssignments = $this->em->getRepository('ActResourceBundle:SimulatedAssignment')->findBy(array(), array('created' => 'DESC'));

                    // Iterate over simulated assignments
                    // We flush for each modification to ensure correct order
                    foreach ($simulatedAssignments as $sim) {
                        $data = json_decode($sim->getSerialized());

                        if ($sim->getEvent() == 'create') {
                            // Delete every assignments with event 'create'
                            $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
                                'project'   => $data->project_id,
                                'resource'  => $data->resource_id,
                                'day'       => \DateTime::createFromFormat('Y-m-d', $data->day)
                            ));
                            $this->em->remove($assignment);
                            $this->em->flush();
                            $nbDelete++;

                        } elseif ($sim->getEvent() == 'update') {
                            // Restore modifications on every assignments with event 'update'
                            $assignment = $this->em->getRepository('ActResourceBundle:Assignment')->findOneBy(array(
                                'project'   => $data->project_id,
                                'resource'  => $data->resource_id,
                                'day'       => \DateTime::createFromFormat('Y-m-d', $data->day)
                            ));

                            $assignment->setWorkload($data->workload_assigned);
                            $assignment->setComment($data->comment);
                            $assignment->setCommonTask(null);
                            $assignment->setSubTask(null);
                            if (isset($data->commontask_id)) { $assignment->setCommonTask($this->em->getRepository('ActResourceBundle:CommonTask')->find($data->commontask_id)); }
                            if (isset($data->subtask_id)) { $assignment->setSubTask($this->em->getRepository('ActResourceBundle:SubTask')->find($data->subtask_id));          }

                            $this->em->persist($assignment);
                            $this->em->flush();
                            $nbEdit++;

                        } elseif ($sim->getEvent() == 'delete') {
                            // Recreate every assignments with event 'delete'
                            $assignment = new Assignment();
                            $assignment->setResource($this->em->getReference('Act\ResourceBundle\Entity\Resource', $data->resource_id));
                            $assignment->setProject($this->em->getReference('Act\ResourceBundle\Entity\Project', $data->project_id));
                            $assignment->setDay(\DateTime::createFromFormat('Y-m-d', $data->day));
                            $assignment->setWorkload($data->workload_assigned);
                            $assignment->setComment($data->comment);
                            $assignment->setCommonTask(null);
                            $assignment->setSubTask(null);
                            if (isset($data->commontask_id)) { $assignment->setCommonTask($this->em->getRepository('ActResourceBundle:CommonTask')->find($data->commontask_id)); }
                            if (isset($data->subtask_id)) { $assignment->setSubTask($this->em->getRepository('ActResourceBundle:SubTask')->find($data->subtask_id));          }

                            $this->em->persist($assignment);
                            $this->em->flush();
                            $nbAdd++;
                        }
                    }

                    // Rollback done
                    // Remove all simulated assignments
                    foreach ($simulatedAssignments as $sim) {
                        $this->em->remove($sim);
                    }

                    // Commit
                    $this->em->flush();
                    $this->em->commit();

                    // Set message
                    $this->session->getFlashBag()->add('info', $this->translator->trans('simulation.end.rollback') . ' : ' . ($nbAdd + $nbEdit) . ' ' . $this->translator->trans('restored.assignments') . ', ' . $nbDelete . ' ' . $this->translator->trans('deleted.assignments'));

                } catch (\Exception $e) {
                    // If any problem, rollback all changes
                    $this->em->rollback();
                    throw $e;
                }
            } else {
                throw new \Exception('Not allowed to commit simulations from someone else');
            }
        } else {
            throw new \Exception('No active simulation found');
        }
    }
}
