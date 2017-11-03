<?php

namespace Act\ResourceBundle\Controller\AdminController;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class BankHolidayAdminController
 * Extends the sonata admin default controller in order to add custom code
 *
 */
class BankHolidayAdminController extends Controller
{
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
        return $this->render('ActResourceBundle:Admin/BankHoliday:list_import.html.twig', array(
            'action'     => 'list',
            'form'       => $formView,
            'datagrid'   => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
        ));
    }

    /**
     * Custom action to import bankholidays from an Excel file
     *
     * @return Response
     * @throws AccessDeniedException
     */
    public function importAction()
    {
        $importer = $this->container->get('act_resource.bankholiday_import');

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

                        $message = '';
                        foreach ($imported as $i) {
                            $class = strtolower(end(explode('\\', get_class($i))));
                            $message .= $i . ' ('.$this->container->get('translator')->trans($class).')<br/>';
                        }

                        $this->addFlash('sonata_flash_success', 'Import de '.count($imported).' objet(s) réussi :<br/> '.$message);
                    } catch (\Exception $e) {
                        $this->addFlash('sonata_flash_info', $e->getMessage());
                    }

                    unlink($filepath);
                } else {
                    $this->addFlash('sonata_flash_info', 'Type du fichier incorrect : '.$form['attachment']->getData()->getMimeType().'<br/>Un Fichier Excel 2007 .xlsx est attendu (application/vnd.ms-excel)');
                }
            } else {
                $this->addFlash('sonata_flash_info', 'Une erreur est survenue lors de l\'upload du fichier<br/>'.$form->getErrorsAsString());
            }
        }

        return $this->render('ActResourceBundle:Admin:BankHoliday/import.html.twig', array(
            'form' => $form->createView()
        ));
    }
}
