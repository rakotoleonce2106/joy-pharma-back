<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\FormDataTableType;
use App\Entity\Form;
use App\Form\FormType;
use App\Repository\FormRepository;
use App\Service\FormService;
use App\Traits\ToastTrait;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FormController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly FormRepository $formRepository,
        private readonly FormService $formService
    ) {}
    #[Route('/form', name: 'admin_form')]
    public function index(Request $request): Response
    {
        $query = $this->formRepository->createQueryBuilder('form');

        $datatable = $this->createNamedDataTable('forms', FormDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/form/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/form/new', name: 'admin_form_new', defaults: ['title' => 'Create form'])]
    public function createAction(Request $request): Response
    {
        $form = new Form();
        $form = $this->createForm(FormType::class, $form, ['action' => $this->generateUrl('admin_form_new')]);
        return $this->handleForm($request, $form, $form, 'create');
    }

    #[Route('/form/{id}/edit', name: 'admin_form_edit', defaults: ['title' => 'Edit form'])]
    public function editAction(Request $request, Form $form): Response
    {   

        $form = $this->createForm(FormType::class, $form, [
            'action' => $this->generateUrl('admin_form_edit', ['id' => $form->getId()])
        ]);
        return $this->handleForm($request, $form, $form, 'edit');
    }

    #[Route('/form/{id}/delete', name: 'admin_form_delete', methods: ['POST'])]
    public  function deleteAction(Form $form): Response
    {
        try {
            $this->formService->deleteForm($form);
            $this->addSuccessToast('Form deleted!', 'The form has been successfully deleted.');
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting the form: ' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_form', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/form/batch-delete', name: 'admin_form_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        try {
            $formIds = $request->request->all('id');
            
            if (empty($formIds)) {
                $this->addWarningToast('No forms selected', 'Please select at least one form to delete.');
                return $this->redirectToRoute('admin_form', [], Response::HTTP_SEE_OTHER);
            }

            $result = $this->formService->batchDeleteForms($formIds);

            if ($result['failure_count'] > 0) {
                $this->addWarningToast(
                    'Partial deletion!',
                    "{$result['success_count']} form(s) deleted successfully. {$result['failure_count']} form(s) could not be deleted."
                );
            } else {
                $this->addSuccessToast(
                    "Forms deleted!",
                    "{$result['success_count']} form(s) have been successfully deleted."
                );
            }
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting forms: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_form', [], Response::HTTP_SEE_OTHER);
    }


    private function handleForm(Request $request, $form, $formEntity, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($action === 'create') {
                $this->formService->createForm($formEntity);
            } else {
                $this->formService->updateForm($formEntity);
            }


            $this->addSuccessToast(
                $action === 'create' ? 'Form created!' : 'Form updated!',
                "The form has been successfully {$action}d."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/form/{$action}.html.twig", 'stream_success', [
                    'form' => $formEntity
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_form', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/form/{$action}.html.twig", [
            'form' => $formEntity
        ]);
    }
}
