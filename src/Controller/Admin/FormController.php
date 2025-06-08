<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\FormDataTableType;
use App\Entity\Form;
use App\Form\FormType;
use App\Repository\FormRepository;
use App\Service\FormService;
use App\Service\MediaFileService;
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
        private readonly FormService $formService,
        private readonly MediaFileService $mediaFileService
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
        $this->formService->deleteForm($form);
        $this->addSuccessToast('Form deleted!', 'The form has been successfully deleted.');
        return $this->redirectToRoute('admin_form');
    }

    #[Route('/form/batch-delete', name: 'admin_form_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        $formIds = $request->request->all('id');
        $this->formService->batchDeleteForms(
            $formIds
        );

        $this->addSuccessToast("Forms deleted!", "The forms have been successfully deleted.");
        return $this->redirectToRoute('admin_form');
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
