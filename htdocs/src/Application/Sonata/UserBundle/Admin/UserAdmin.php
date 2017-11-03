<?php

namespace Application\Sonata\UserBundle\Admin;

use Sonata\UserBundle\Admin\Model\UserAdmin as BaseUserAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class UserAdmin extends BaseUserAdmin
{
    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
          ->addIdentifier('username')
          ->add('email')
          ->add('groups')
          ->add('enabled', null, array('editable' => true))
          ->add('locked', null, array('editable' => true))
          ->add('createdAt', 'date')
          ->add('resource', null, array(
              'label' => $this->translator->trans('resource')
          ))
        ;

        if ($this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            $listMapper
              ->add('impersonating', 'string', array('template' => 'SonataUserBundle:Admin:Field/impersonating.html.twig'))
            ;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filterMapper)
    {
        $filterMapper
          ->add('username')
          ->add('locked')
          ->add('email')
          ->add('groups')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
          ->with('General', array('class' => 'col-md-6'))
              ->add('username')
              ->add('email')
              ->add('plainPassword', 'text', array(
                  'required' => (!$this->getSubject() || is_null($this->getSubject()->getId()))
                ))
              ->add('locked', null, array('required' => false))
              ->add('enabled', null, array('required' => false))
          ->end()
        ;

        if ($this->getSubject() && !$this->getSubject()->hasRole('ROLE_SUPER_ADMIN')) {
            $formMapper
                ->with('Rights', array('class' => 'col-md-6'))
                    ->add('groups', 'sonata_type_model', array(
                      'required' => false,
                      'expanded' => false,
                      'multiple' => true,
                      'btn_add'  => false
                    ))
                    ->add('realRoles', 'sonata_security_roles', array(
                      'label'    => 'form.label_roles',
                      'expanded' => false,
                      'multiple' => true,
                      'required' => false
                    ))
                ->end()
            ;
        }
    }

    /**
     * Disable ACL on this admin
     */
    public function isAclEnabled()
    {
        return false;
    }
}
