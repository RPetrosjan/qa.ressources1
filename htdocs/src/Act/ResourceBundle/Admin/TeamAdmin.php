<?php

namespace Act\ResourceBundle\Admin;

use Act\MainBundle\Admin\CustomAdmin as Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Act\ResourceBundle\Entity\ResourceRepository;

/**
 * Class TeamAdmin
 *
 * Admin interface for the Team entity
 */
class TeamAdmin extends Admin
{
    /**
     * Default Datagrid values
     */
    protected $datagridValues = array(
        '_sort_order' => 'ASC',
        '_sort_by'    => 'name'
    );

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->classnameLabel = $this->translator->trans('team');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('name', 'text', array(
                'label' => $this->translator->trans("name"),
                'template' => 'ActResourceBundle:Admin:Team/name.html.twig',
                'editable' => true
            ))
            ->add('manager', 'entity', array(
                'label' => $this->translator->trans("manager"),
                'class' => 'Act\ResourceBundle\Entity\Resource'
            ))
            ->add('getNbResources', 'integer', array(
                'label' => $this->translator->trans("resources.nbr"),
                'mapped' => false
            ))
            ->add('_action', 'string', array(
                'actions' => array(
                    'edit' => array(),
                    'profiles' => array('template' => 'ActResourceBundle:Admin:Team/profiles_btn.html.twig'),
                    'members' => array('template' => 'ActResourceBundle:Admin:Team/members_btn.html.twig'),
                    'edit' => array(),
                    'delete' => array()
                )
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array(
                'label' => $this->translator->trans("name")
            ))
            ->add('color', null, array(
                'label' => $this->translator->trans("color"),
                'required' => true,
                'attr' => array(
                    'class' => 'colorpicker'
                )
            ))
        ;

        // Adding manager choice field if editing entity
        if ($this->id($this->getSubject())) {
            $id = $this->id($this->getSubject());
            $formMapper
                ->add('manager', 'entity', array(
                    'label' => $this->translator->trans('manager'),
                    'class' => 'Act\ResourceBundle\Entity\Resource',
                    'required' => false,
                    'query_builder' =>  function (ResourceRepository $repo) use ($id) {
                                            return $repo->getResourcesForThisTeam($id);
                                        }
                ));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array(
                'label' => $this->translator->trans("name")
            ))
            ->add('manager', null, array(
                'label' => $this->translator->trans("manager")
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function preRemove($team)
    {
        $container = $this->getConfigurationPool()->getContainer();
        $container->get('act_resource.team.manager')->removeTeamDependencies($team);
    }
}
