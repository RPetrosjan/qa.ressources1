<?php

namespace Act\ResourceBundle\Admin;

use Act\MainBundle\Admin\CustomAdmin as Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Description of PostAdmin
 *
 * @author ajansen
 */
class CommonTaskAdmin extends Admin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array('label' => $this->translator->trans("name")))
            ->add('start', 'date', array(
                'label'=>$this->translator->trans('date.start'),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
                'attr' => array(
                    'class' => 'datepicker'
                ))
            )
            ->add('end', 'date', array(
                'label'=>$this->translator->trans('date.end'),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
                'attr' => array(
                    'class' => 'datepicker'
                ))
            )
            ->add('workload_sold', 'number', array(
                'label' => $this->translator->trans('workload.sold')
            ))
            ->add('teams', null, array(
                'label' => $this->translator->trans('teams'),
                'required' => false
            ))
            ->add('teamprofiles', null, array(
                'label' => $this->translator->trans('profiles'),
                'required' => false
            ))
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array("label"=>$this->translator->trans("name")))
            ->add('project', null, array("label"=>$this->translator->trans("project")))
            ->add("metatask", null, array("label"=>$this->translator->trans("commontask.ParentMetatask")))
            ->add('teams', null, array("label"=>$this->translator->trans("teams")))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('project', null, array("label"=>$this->translator->trans("project")))
            ->add("metatask", null, array("label"=>$this->translator->trans("commontask.ParentMetatask")))
            ->add('name', null, array("label"=>$this->translator->trans("name")))
            ->add('teams', null, array("label"=>$this->translator->trans("teams")))
            ->add('_action', 'string', array('template' => 'ActResourceBundle:Admin:Tasks/BtnCustomCommonTasks.html.twig'))
            ;
    }
}
