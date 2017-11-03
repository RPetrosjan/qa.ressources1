<?php

namespace Act\ResourceBundle\Controller\AdminController;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery as ProxyQueryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class ProjectAdminController
 * Extends the sonata admin default controller in order to add custom code
 *
 */
class ProjectAdminController extends Controller
{
    /**
     * Batch action - export a project into an excel file
     *
     * @param ProxyQueryInterface $selectedModelQuery
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function batchActionExport(ProxyQueryInterface $selectedModelQuery)
    {
        $request  = $this->get('request');
        $idx      = $request->get('idx');
        $projects = array();

        // Gather projects to export
        if ($request->get('all_elements') == 'on') {
            $projects = $this->getDoctrine()->getRepository('ActResourceBundle:Project')->findAll();
        } else {
            for ($i = 0; $i < count($idx); $i++) {
                $projects[] = $this->getDoctrine()->getRepository('ActResourceBundle:Project')->find($idx[$i]);
            }
        }

        // Generate the file content
        $content = $this->get('act_resource.project_export')->exportMultiple($projects);

        if ($content == null || strlen($content) == 0) {
            $this->get('session')->getFlashBag()->add('warning', $this->container->get('translator')->trans('export.project.error'));

            return $this->redirect($this->generateUrl('admin_act_resource_project_list'));
        }

        // Return the proper headers for the file
        return $content;
    }

    /**
     * Return the Response object associated to the list action
     * Used to add a custom action in the "Actions" menu
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     *
     * @return Response
     */
    public function listAction()
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        if ($listMode = $this->getRequest()->get('_list_mode')) {
            $this->admin->setListMode($listMode);
        }

        $datagrid = $this->admin->getDatagrid();
        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getExtension('form')->renderer->setTheme($formView, $this->admin->getFilterTheme());

        // Override the default theme to set our custom html in the "Actions" menu
        return $this->render('ActResourceBundle:Admin/Project:list_import.html.twig', array(
            'action'     => 'list',
            'form'       => $formView,
            'datagrid'   => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
        ));
    }

    /**
     * Custom action to import a project from an Excel file
     * It can be an existing project or a new one
     *
     * @return Response
     * @throws AccessDeniedException
     */
    public function importAction()
    {
        if (false === $this->admin->isGranted('OPERATOR')) {
            throw new AccessDeniedException();
        }

        $importer = $this->container->get('act_resource.project_import');

        // Generate the upload form
        $form = $this->createFormBuilder()
            ->add('attachment', 'file',
                array(
                    'required' => true,
                    'label' => 'Fichier',
                )
            )->getForm();

        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                $mime = $form['attachment']->getData()->getMimeType();
                if ($mime == 'application/vnd.ms-excel' || $mime == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' || $mime == 'application/zip') {
                    $filepath = $this->get('kernel')->getRootDir().'/../web/uploads/tmp/'.$form['attachment']->getData()->getClientOriginalName();
                    $form['attachment']->getData()->move($this->get('kernel')->getRootDir().'/../web/uploads/tmp', $form['attachment']->getData()->getClientOriginalName());

                    try {
                        $imported = $importer->import($filepath);

                        // New objects
                        if (count($imported['new']) > 0) {
                            $message = '';
                            foreach ($imported['new'] as $i) {
                                $message .= $i.'<br/>';
                            }
                            $this->addFlash('sonata_flash_success', 'Import de '.count($imported['new']).' tâche(s) réussi :<br/> '.$message);
                        }

                        // Updated objects
                        if (count($imported['updated']) > 0) {
                            $message = '';
                            foreach ($imported['updated'] as $i) {
                                $message .= $i.'<br/>';
                            }
                            $this->addFlash('sonata_flash_success', 'Mise à jour de '.count($imported['updated']).' tâche(s) réussie :<br/> '.$message);
                        }
                    } catch (\Exception $e) {
                        $this->addFlash('sonata_flash_info', $e->getMessage());
                    }

                    unlink($filepath);
                } else {
                    $this->addFlash('sonata_flash_info', 'Type du fichier incorrect : '.$form['attachment']->getData()->getMimeType().'<br/>Un Fichier Excel 2007 .xlsx est attendu (application/vnd.ms-excel)');
                }
            } else {
                $this->addFlash('sonata_flash_info', 'Une erreur est survenue lors de l\'upload du fichier');
            }
        }

        return $this->render('ActResourceBundle:Admin:Project/import.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
