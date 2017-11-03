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
class CommentAdmin extends Admin
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
        $this->classnameLabel = $this->translator->trans('comment');
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper->add('content', null, array("label"=>$this->translator->trans("comment")));

        if (!$this->isChild()) {
            // Only show project form field if not accessed from project list
            $formMapper->add('project', 'sonata_type_model');
        }
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('user', null, array("label"=>$this->translator->trans("user")))
            ->add('project', null, array("label"=>$this->translator->trans("project")))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('user', null, array(
                'label' => $this->translator->trans("author")
            ))
            ->add('content', null, array(
                'label' => $this->translator->trans("comment")
            ))
            ->add('_action', 'string', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array()
                )
            ))
        ;
    }

    public function getNewInstance()
    {
        $object = parent::getNewInstance();
        $user = $this->getConfigurationPool()->getContainer()->get('security.context')->getToken()->getUser();

        // Define the author of the comment
        $object->setUser($user);

        return $object;
    }
}
