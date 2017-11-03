<?php

namespace Act\ResourceBundle\Admin;

use Act\MainBundle\Admin\CustomAdmin as Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Application\Sonata\UserBundle\Entity\UserRepository;

/**
 * Description of PostAdmin
 *
 * @author ajansen
 */
class ResourceAdmin extends Admin
{
    /**
     * Set the parent Admin class
     */
    protected $parentAssociationMapping = 'team';

    /**
     * Default Datagrid values
     */
    protected $datagridValues = array(
        '_sort_order' => 'ASC',
        '_sort_by'    => 'name'
    );

    // Configure class data
    public function configure()
    {
        $this->classnameLabel = $this->translator->trans('resource');
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        // Edit or create mode ?
        $resource = null;
        if ($this->id($this->getSubject())) {
            // Edit
            $resource = $this->getSubject();
        }

        $formMapper
            ->add('name', 'text', array(
                'label' => $this->translator->trans("name")
            ))
            ->add('nameShort', 'text', array(
                'label' => $this->translator->trans("name.short")
            ))
            ->add('start', null, array(
                'label' => $this->translator->trans("date.start"),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
                'attr' => array(
                    'class' => 'datepicker'
                )
            ))
            ->add('end', null, array(
                'label' => $this->translator->trans("date.end"),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => false,
                'attr' => array(
                    'class' => 'datepicker'
                )
            ))
            ->add('days_per_week', null, array(
                'label' => $this->translator->trans("working.days.per.week")
            ))
            ->add('team', 'entity', array(
                'label' => $this->translator->trans("teams"),
                'required' => true,
                'class' => 'Act\ResourceBundle\Entity\Team'
            ))
            ->add('user', 'entity', array(
                'label' => $this->translator->trans("associated.user"),
                'required' => false,
                'class' => 'Application\Sonata\UserBundle\Entity\User',
                'query_builder' =>  function (UserRepository $repo) use ($resource) {
                    if ($resource != null && $resource->getUser() != null) {
                        return $repo->getUnlinkedUsers($resource->getUser());
                    } else {
                        return $repo->getUnlinkedUsers(null);
                    }
                }
            ))
            ->add('location', 'entity', array(
                'label' => $this->translator->trans("location"),
                'class' => 'Act\ResourceBundle\Entity\Location'
            ))
            ->add('information')
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('nameShort', null, array(
                'label' => $this->translator->trans("code")
            ))
            ->add('name', null, array(
                'label' => $this->translator->trans("name")
            ))
            ->add('start', 'doctrine_orm_date', array(
                    'label' => $this->translator->trans('date.start'),
                ), 'date', array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => false,
                    'attr' => array(
                      'class' => 'datepicker'
                    )
                )
            )
            ->add('end', 'doctrine_orm_date', array(
                  'label' => $this->translator->trans('date.end')
                ), 'date', array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => false,
                    'attr' => array(
                      'class' => 'datepicker'
                    )
                )
            )
            ->add('days_per_week', null, array(
                'label' => $this->translator->trans("working.days.per.week.short")
            ))
            ->add('team', null, array(
                'label' => $this->translator->trans("team"),
                'class' => 'Act\ResourceBundle\Entity\Resource'
            ))
            ->add('user', null, array(
                'label' => $this->translator->trans("associated.user"),
                'class' => 'Application\Sonata\UserBundle\Entity\User'
            ))
            ->add('location', null, array(
                'label' => $this->translator->trans("location"),
                'class' => 'Act\ResourceBundle\Entity\Location'
            ))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('nameShort', 'text', array(
                'label' => $this->translator->trans("code")
            ))
            ->add('name', 'text', array(
                'label' => $this->translator->trans("name"),
                'template' => 'ActResourceBundle:Admin:Resource/name.html.twig'
            ))
            ->add('start', 'date', array(
                'label' => $this->translator->trans("date.start")
            ))
            ->add('end', 'date', array(
                'label' => $this->translator->trans("date.end")
            ))
            ->add('days_per_week', null, array(
                'label' => $this->translator->trans("working.days.per.week.short"),
                'editable' => true
            ))
            ->add('team', 'entity', array(
                'label' => $this->translator->trans("team"),
                'class' => 'Act\ResourceBundle\Entity\Team'
            ))
            ->add('location', 'entity', array(
                'label' => $this->translator->trans("location"),
                'class' => 'Act\ResourceBundle\Entity\Location'
            ))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array()
                )
            ))
        ;
    }

    /**
     * Code to execute before deleting a Resource
     */
    public function preRemove($object)
    {
        $em = $this->getConfigurationPool()->getContainer()->get('doctrine')->getManager();

        // If the resource is a manager of his team, set it to NULL
        if ($object->getTeam()->getManager() != null && $object->getTeam()->getManager()->getId() == $object->getId()) {
            $object->getTeam()->setManager(null);
            $em->persist($object->getTeam());
            $em->flush();
        }
    }

    /**
     * Code to execute before updating a Resource
     */
    public function preUpdate($object)
    {
        $em = $this->getConfigurationPool()->getContainer()->get('doctrine')->getManager();

        // Retrieve the original entity
        $original = $em->getUnitOfWork()->getOriginalEntityData($object);

        $originalTeamId = ($original && isset($original['team']) ? $original['team']->getId() : -1);
        $newTeamId = ($object->getTeam() != null ? $object->getTeam()->getId() : -1);

        // only if team is changed
        if ($originalTeamId != $newTeamId) {
            //retrieve team where the resource manager is
            $teamEntityManager = $em->getRepository('ActResourceBundle:Team');
            $TeamWhereResourceIsManager = $teamEntityManager->getTeamWithManagerObject($object);

            //only if the Resource is a manager of a team
            if ($TeamWhereResourceIsManager != NULL) {
                //set no manager to the team
                $TeamWhereResourceIsManager->setManager();
            }
        }
    }
}
