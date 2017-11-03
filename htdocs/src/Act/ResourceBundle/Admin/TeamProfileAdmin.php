<?php

namespace Act\ResourceBundle\Admin;

use Act\MainBundle\Admin\CustomAdmin as Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class TeamProfileAdmin extends Admin
{
    /**
     * Set the parent Admin class
     */
    protected $parentAssociationMapping = 'team';

    /**
     * Default Datagrid values
     */
    protected $datagridValues = array(
        '_sort_order'   => 'ASC',
        '_sort_by'      => 'name'
    );

    // Configure class data
    public function configure()
    {
        $this->classnameLabel = $this->translator->trans('profile');
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array('label' => $this->translator->trans("name")))
        ;

        if (!$this->isChild()) {
            // Only show project form field if not accessed from project list
            $formMapper->add('team', 'entity', array(
                'label' => $this->translator->trans("team"),
                'class' => 'Act\ResourceBundle\Entity\Team'
            ));
        }
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array('label' => $this->translator->trans("name")))
            ->add('team', null, array('label' => $this->translator->trans("team")))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('name', 'text', array(
                'label' => $this->translator->trans("name")
            ))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array()
                )
            ))
        ;

        if (!$this->isChild()) {
            $listMapper->add('team', 'entity', array(
                'label' => $this->translator->trans("team"),
                'class' => 'Act\ResourceBundle\Entity\Team'
            ));
        }
    }
}
