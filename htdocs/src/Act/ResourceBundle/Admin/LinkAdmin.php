<?php

namespace Act\ResourceBundle\Admin;

use Act\MainBundle\Admin\CustomAdmin as Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Act\ResourceBundle\Entity\Link;

/**
 * Description of PostAdmin
 *
 * @author ajansen
 */
class LinkAdmin extends Admin
{
    /**
     * Set the parent Admin class
     */
    protected $parentAssociationMapping = 'project';

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
        $this->classnameLabel = $this->translator->trans('link');
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('name', 'text', array(
                'label' => $this->translator->trans("name")
            ))
            ->add('type', 'choice', array(
                'label' => $this->translator->trans("path"),
                'choices' => array(
                    Link::LINK_URL => $this->translator->trans("url"),
                    Link::LINK_FILE => $this->translator->trans("file")
                )
            ))
            ->add('url', 'text', array(
                'label' => $this->translator->trans("url"),
                'required' => false
            ))
            ->add('file', 'file', array(
                'label' => $this->translator->trans("file"),
                'required' => false
            ))
        ;

        if (!$this->isChild()) {
            // Only show project form field if not accessed from project list
            $formMapper->add('project', 'sonata_type_model');
        }
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name', null, array(
                'label' => $this->translator->trans("name")
            ))
            ->add('type', null, array(
                'label' => $this->translator->trans("type")
            ))
            ->add('url', null, array(
                'label' => $this->translator->trans("path")
            ))
            ->add('project', null, array(
                'label' => $this->translator->trans("project")
            ))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('name', 'text', array(
                'label' => $this->translator->trans('name')
            ))
            ->add('type', 'integer', array(
                'label' => $this->translator->trans('type'),
                'template' => 'ActResourceBundle:Admin:Link/type_field.html.twig'
            ))
            ->add('url', 'string', array(
                'label' => $this->translator->trans('path'),
                'template' => 'ActResourceBundle:Admin:Link/url_field.html.twig'
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
