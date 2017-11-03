<?php

namespace Act\ResourceBundle\Admin;

use Act\MainBundle\Admin\CustomAdmin as Admin;
use Act\ResourceBundle\Controller\ProjectController;
use Act\ResourceBundle\Controller\RestController;
use Act\ResourceBundle\Entity\Client;
use Act\ResourceBundle\Entity\ClientRepository;
use Act\ResourceBundle\Entity\Project;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\ORM\EntityRepository;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Act\ResourceBundle\Entity\Alibbez;
use Symfony\Component\Validator\Constraints\DateTime;


class ProjectAdmin extends Admin
{
    /**
     * Default Datagrid values
     */
    protected $datagridValues = array(
        '_sort_order' => 'ASC',
        '_sort_by'    => 'name'
    );

    /**
     * Set the child Admin class
     */
    protected $child = 'project';

    public function configure()
    {
        $this->classnameLabel = $this->translator->trans('project');
        //Added By Ruben 26/10/2017 Here Add TWIG ho a can integreationg JS oder CSS files
        // src/Act/RessourceBundle/ressources/views/Project/AddScriptProject.html.twig
        $this->setTemplate('edit', 'ActResourceBundle:Project:AddScriptProject.html.twig');
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        // Adding our custom import action into the route collection
        $collection->add('import', 'import');
    }

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {


        $builder = $formMapper->getFormBuilder();
        $em = $this->getConfigurationPool()->getContainer()->get('doctrine')->getManager();

        //  Ruben 30/10/2017
        // Pres bumit pour verificatiob si le client (societe exist oui pas)

        //En cas si on choisisre Recuperer liste de projet par Alibeez on verifie si le client exist a la base
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use($em) {

            //Recuperation des data(s) du formumlaire
            $data = $event->getData();

            //Si le valeur clint exist
            if(isset($data['client']))
            {
                // Chercher a la base du Nim client
                $client = $em->getRepository('ActResourceBundle:Client')->findOneBy(array('name' => $data['client_alibeez']));
                //Si le client n'existe pas on cree
                if($client==null) {
                    $client = new Client();
                    $client->setName($data['client_alibeez']);
                    $client->setNameShort($data['clientShort_alibeez']);
                    $client->setContactName('');
                    $em->persist($client);
                    $em->flush();
                }

                //Apres de nouveu recuperer vrai ID du client et mettre a jour event
                $data['client'] = $client->getId();
                $event->setData($data);
            }

        });

        $formMapper

            //Ajoutee par Ruben 25/10/2017
            // sonata_type_choice_field_mask permet de faire cacher / affisher les fields en ligne
            ->add('project_signee', 'sonata_type_choice_field_mask', array(
                'label' => $this->translator->trans("project.signed").' ?',
                'placeholder' => $this->translator->trans("choose.answer"),
                'choices' => array(1 => $this->translator->trans("yes"),0 => $this->translator->trans("no")),
                /// Mappging pour definir les fields affisher par Yes/No
                'map' => array(
                    1 => array('name_alibeez'),
                    0 => array('name'),
                ),
                'attr' => array('id','alibez_list'),
            ))

            ->add('name_alibeez','choice', array(
                'mapped' => false,
                'choices' => Alibbez::getAlibbezProjects(),
                'label' => $this->translator->trans("name"),
                ))

            ->add('name', 'text', array(
                'label' => $this->translator->trans("name"),
               /// 'attr' => array('value'=>'Ruben'),
            ))
            ->add('nameShort', 'text', array(
                'label' => $this->translator->trans("name.short")
            ))
            ->add('clientShort_alibeez', 'hidden', array(
                'required' => false,
                'mapped' => false,
            ))
            ->add('client', 'entity', array(
                'label' => $this->translator->trans("client"),
                'class' => 'Act\ResourceBundle\Entity\Client',
                'required' => false
            ))
            ->add('client_alibeez', 'hidden', array(
                'required' => false,
                'mapped' => false,
            ))
            ->add('start', null, array(
                'label' => $this->translator->trans("date.start"),
                'widget' => 'single_text',
                'required' => true,
                'format' => 'dd/MM/yyyy',
                'attr' => array(
                    'class' => 'datepicker start'
                )
            ))
            ->add('end', null, array(
                'label' => $this->translator->trans("date.end"),
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'required' => true,
                'attr' => array(
                    'class' => 'datepicker end'
                )
            ))
            ->add('cpf', 'entity', array(
                'label' => $this->translator->trans("cpf"),
                'class' => 'Act\ResourceBundle\Entity\Resource',
                'required' => false
            ))
            ->add('color', null, array(
                'label' => $this->translator->trans("color"),
                'required' => true,
                'attr' => array('class' => 'colorpicker')
            ))
            ->add('active', null, array(
                'label' => $this->translator->trans("activated?"),
                'required' => false
            ))
            ->add('typePresaleGT70', null, array(
                'label' => $this->translator->trans("project.type.presale.gt70"),
                'required' => false
            ))
            ->add('typePresaleLT70', null, array(
                'label' => $this->translator->trans("project.type.presale.lt70"),
                'required' => false
            ))
            ->add('typeSigned', null, array(
                'label' => $this->translator->trans("project.type.signed"),
                'required' => false
            ))
            ->add('typeHoliday', null, array(
                'label' => $this->translator->trans("project.type.holiday"),
                'required' => false
            ))
            ->add('typeInternal', null, array(
                'label' => $this->translator->trans("project.type.internal"),
                'required' => false
            ))
            ->add('typeResearch', null, array(
                'label' => $this->translator->trans("project.type.research"),
                'required' => false
            ))
        ;

       /* $formMapper->getFormBuilder()->addEventListener(FormEvents::PRE_SET_DATA,function (FormEvent $event) use ($formMapper){

            $form = $event->getForm();
            $data = $event->getData();

            dump(sizeof($data));
            if(sizeof($data)>0)
            {
                dump($data->getName());
            }


            /*

            if($formMapper->get('name')){
                $formMapper->remove('name');
            }
            else{

            }
        });*/
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('name')
            ->add('project_signee')
            ->add('nameShort', null, array(
                'label' => $this->translator->trans("name.short")
            ))
            ->add('client', null, array(
                'label' => $this->translator->trans("client"),
                'class' => 'Act\ResourceBundle\Entity\Client'
            ))
            ->add('start', 'doctrine_orm_date', array(
                    'label' => $this->translator->trans('date.start'),
                ), 'date', array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => false,
                    'attr' => array(
                        'class' => 'datepicker'
                    )
                )
            )
            ->add('end', 'doctrine_orm_date', array(
                    'label' => $this->translator->trans('date.end')
                ), 'date', array(
                    'widget' => 'single_text',
                    'format' => 'dd/MM/yyyy',
                    'required' => false,
                    'attr' => array(
                        'class' => 'datepicker'
                    )
                )
            )
            ->add('cpf', null, array(
                'label' => $this->translator->trans("cpf"),
                'class' => 'Act\ResourceBundle\Entity\Resource'
            ))
            ->add('active', null, array(
                'label' => $this->translator->trans("activated?")
            ))
            ->add('typePresaleGT70', null, array(
                'label' => $this->translator->trans("project.type.presale.gt70")
            ))
            ->add('typePresaleLT70', null, array(
                'label' => $this->translator->trans("project.type.presale.lt70")
            ))
            ->add('typeSigned', null, array(
                'label' => $this->translator->trans("project.type.signed")
            ))
            ->add('typeHoliday', null, array(
                'label' => $this->translator->trans("project.type.holiday")
            ))
            ->add('typeInternal', null, array(
                'label' => $this->translator->trans("project.type.internal")
            ))
            ->add('typeResearch', null, array(
                'label' => $this->translator->trans("project.type.research")
            ))
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('active', 'boolean', array(
                'label' => $this->translator->trans("activated?"),
                'editable' => true
            ))
            ->add('project_signee', 'boolean', array(
                'label' => $this->translator->trans("project.signed"),
                'editable' => false
            ))
            ->add('nameShort', 'text', array(
                'label' => $this->translator->trans("code")
            ))
            ->add('name', 'text', array(
                'label' => $this->translator->trans("name"),
                'template' => 'ActResourceBundle:Admin:Project/name.html.twig',
                'editable' => true
            ))
            ->add('start', 'date', array(
                'label' => $this->translator->trans("date.start")
            ))
            ->add('end', 'date', array(
                'label' => $this->translator->trans("date.end")
            ))
            ->add('_action', 'actions', array(
                'actions' => array(
                    'show planning' => array('template' => 'ActResourceBundle:Admin:Project/show_planning_btn.html.twig'),
                    'show planning advanced' => array('template' => 'ActResourceBundle:Admin:Project/show_planning_advanced_btn.html.twig'),
                    'edit' => array(),
                    'delete' => array(),
                    'divider' => array('template' => 'ActResourceBundle:Admin:divider.html.twig'),
                    'links' => array('template' => 'ActResourceBundle:Admin:Project/links_btn.html.twig'),
                    'comments' => array('template' => 'ActResourceBundle:Admin:Project/comments_btn.html.twig'),
                    'project.cpts.manage' => array('template' => 'ActResourceBundle:Admin:Project/cpts_btn.html.twig'),
                    'project.tasks' => array('template' => 'ActResourceBundle:Admin:Project/tasks_btn.html.twig'),
                    'project.tasks.export' => array('template' => 'ActResourceBundle:Admin:Project/task_export_btn.html.twig'),
                    'project.prefered_teams' => array('template' => 'ActResourceBundle:Admin:Project/prefered_teams_btn.html.twig'),
                )
            ))
        ;
    }

    /**
     * Returns the list of batchs actions
     *
     * @return array the list of batchs actions
     */
    public function getBatchActions()
    {
        $actions = parent::getBatchActions();

        // Add our custom bacth action
        $actions['export'] = array(
            'label' => $this->translator->trans("export"),
            'ask_confirmation' => false
        );

        return $actions;
    }
}
