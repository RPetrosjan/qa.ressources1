<?php

namespace Act\ResourceBundle\Admin;

use Act\MainBundle\Admin\CustomAdmin as Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;

/**
 * BankHolidayAdmin
 * Contains code to manage the bankholiday objects
 *
 */
class BankHolidayAdmin extends Admin
{
    /**
     * Set the parent Admin class
     */
    protected $parentAssociationMapping = 'locations';

    /**
     * Default Datagrid values
     */
    protected $datagridValues = array(
        '_sort_order'   => 'DESC',
        '_sort_by'      => 'start'
    );

    // Configure class data
    public function configure()
    {
        $this->classnameLabel = $this->translator->trans('bankholidays');
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        // Adding our custom import action into the route collection
        $collection->add('import', 'import');
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array(
                'label' => $this->translator->trans('name')
            ))
            ->add('locations', 'entity', array(
                'label' => $this->translator->trans('locations'),
                'class' => 'Act\ResourceBundle\Entity\Location',
                'expanded' => true,
                'multiple' => true
            ))
            ->add('start', 'date', array(
                'label'=>$this->translator->trans('date'),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
                'attr' => array(
                    'class' => 'datepicker'
                ))
            )
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('locations', null, array(
                'label' => $this->translator->trans('locations')
            ))
            ->add('start', 'doctrine_orm_date', array(
                    'label' => $this->translator->trans('date')
                ),
                'date',
                array(
                    'widget' => 'single_text',
                    'attr' => array(
                      'class' => 'datepicker'
                    )
                )
            )
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('start', null, array(
                'label' => $this->translator->trans('date')
            ))
            ->add('name', null, array(
                'label' => $this->translator->trans('name'),
                'editable' => true
            ))
            ->add('locations', null, array(
                'label' => $this->translator->trans('locations')
            ))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array()
                )
            ))
        ;
    }
}
