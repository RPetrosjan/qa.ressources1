<?php

namespace Act\ResourceBundle\Admin;

use Act\MainBundle\Admin\CustomAdmin as Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class ClientAdmin extends Admin
{
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
        $this->classnameLabel = $this->translator->trans('client');
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array("label"=>$this->translator->trans("name")))
            ->add('nameShort', 'text', array("label"=>$this->translator->trans("name.short")))
            ->add('contactName', 'text', array("label"=>$this->translator->trans("client.contact.name")))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array("label"=>$this->translator->trans("name")))
            ->add('nameShort', null, array("label"=>$this->translator->trans("name.short")))
            ->add('contactName', null, array("label"=>$this->translator->trans("client.contact.name")))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('nameShort', null, array(
                'label' => $this->translator->trans("code")
            ))
            ->add('name', null, array(
                'label' => $this->translator->trans("name"),
                'editable' => true
            ))
            ->add('contactName', null, array(
                'label' => $this->translator->trans("client.contact.name"),
                'editable' => true
            ))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array()
                )
            ));
    }
}
