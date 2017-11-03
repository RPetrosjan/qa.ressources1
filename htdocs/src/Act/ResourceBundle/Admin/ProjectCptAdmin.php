<?php

namespace Act\ResourceBundle\Admin;

use Act\MainBundle\Admin\CustomAdmin as Admin;
use Act\ResourceBundle\Entity\TeamRepository;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

/**
 * Description of PostAdmin
 *
 * @author ajansen
 */
class ProjectCptAdmin extends Admin
{
    /**
     * Set the parent Admin class
     */
    protected $parentAssociationMapping = 'project';

    /**
     * Default Datagrid values
     */
    protected $datagridValues = array(
        '_sort_order' => 'ASC',
        '_sort_by'    => 'project'
    );

    // Configure class data
    public function configure()
    {
        $this->classnameLabel = $this->translator->trans('cpt');
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $projectCPT = $this->getSubject();
        $project    = $projectCPT->getProject();

        if ($this->id($this->getSubject())) {
            // EDIT
            $formMapper
              ->add('team', 'entity', array(
                  'label' => $this->translator->trans('team'),
                  'class' => 'Act\ResourceBundle\Entity\Team',
                  'query_builder' =>  function (TeamRepository $repo) use ($projectCPT) {
                      // Only teams that have not yet a CPT defined + current selected one
                      return $repo->getTeamsWithoutCPTForProject($projectCPT->getProject(), $projectCPT->getTeam());
                  }
                ))
              ->add('resource', 'entity', array(
                  'label' => $this->translator->trans("resource"),
                  'class' => 'Act\ResourceBundle\Entity\Resource'
                ))
            ;
        } else {
            // CREATE
            $formMapper
              ->add('team', 'entity', array(
                  'label' => $this->translator->trans('team'),
                  'class' => 'Act\ResourceBundle\Entity\Team',
                  'query_builder' =>  function (TeamRepository $repo) use ($project) {
                      // Only teams that have not yet a CPT defined
                      return $repo->getTeamsWithoutCPTForProject($project);
                  }
                ))
              ->add('resource', 'entity', array(
                  'label' => $this->translator->trans("resource"),
                  'class' => 'Act\ResourceBundle\Entity\Resource'
                ))
            ;
        }
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('project')
            ->add('resource')
            ->add('team')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('team', 'entity', array(
                'label' => $this->translator->trans("team"),
                'required' => true,
                'class' => 'Act\ResourceBundle\Entity\Team'
            ))
            ->add('resource', 'entity', array(
                'label' => $this->translator->trans("resource"),
                'required' => false,
                'class' => 'Act\ResourceBundle\Entity\Resource'
            ));
            if (!$this->isChild()) {
                $listMapper->add('project', 'entity', array(
                  'label' => $this->translator->trans("project"),
                  'class' => 'Act\ResourceBundle\Entity\Project'
                ));
            }
            $listMapper->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array()
                )
            ))
        ;

    }

}
