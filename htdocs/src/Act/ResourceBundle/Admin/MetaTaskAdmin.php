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
class MetaTaskAdmin extends Admin
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
            ->add('name', null, array('label' => $this->translator->trans("name")))
            ->add('project', null, array('label' => $this->translator->trans("metatask.ParentProject")))
            ->add('start', null, array('label' => $this->translator->trans("date.start")))
            ->add('end', null, array('label' => $this->translator->trans("date.end")))
            ->add('teams', null, array('label' => $this->translator->trans("teams")))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('project', null, array('label' => $this->translator->trans("metatask.ParentProject")))
            ->add('name', null, array('label' => $this->translator->trans("name")))
            ->add('start', null, array('label' => $this->translator->trans("date.start")))
            ->add('end', null, array('label' => $this->translator->trans("date.end")))
            ->add('workload_sold', null, array('label' => $this->translator->trans("workload.sold")))
            ->add('teams', null, array('label' => $this->translator->trans("teams")))
            ->add('_action', 'string', array('template' => 'ActResourceBundle:Admin:Tasks/BtnCustomMetaTasks.html.twig'))
            ;
    }
}
