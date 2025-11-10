<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\ManufacturerDataTableType;
use App\Entity\Manufacturer;
use App\Form\ManufacturerType;
use App\Repository\ManufacturerRepository;
use App\Service\ManufacturerService;
use App\Traits\ToastTrait;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ManufacturerController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly ManufacturerRepository $manufacturerRepository,
        private readonly ManufacturerService $manufacturerService
    ) {}
    #[Route('/manufacturer', name: 'admin_manufacturer', defaults: ['title' => 'Manufacturer'])]
    public function index(Request $request): Response
    {
        $query = $this->manufacturerRepository->createQueryBuilder('manufacturer');

        $datatable = $this->createNamedDataTable('manufacturers', ManufacturerDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/manufacturer/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/manufacturer/new', name: 'admin_manufacturer_new', defaults: ['title' => 'Create manufacturer'])]
    public function createAction(Request $request): Response
    {
        $manufacturer = new Manufacturer();
        $form = $this->createForm(ManufacturerType::class, $manufacturer, ['action' => $this->generateUrl('admin_manufacturer_new')]);
        return $this->handleManufacturerForm($request, $form, $manufacturer, 'create');
    }

    #[Route('/manufacturer/{id}/edit', name: 'admin_manufacturer_edit', defaults: ['title' => 'Edit manufacturer'])]
    public function editAction(Request $request, Manufacturer $manufacturer): Response
    {

        $form = $this->createForm(ManufacturerType::class, $manufacturer, [
            'action' => $this->generateUrl('admin_manufacturer_edit', ['id' => $manufacturer->getId()])
        ]);
        return $this->handleManufacturerForm($request, $form, $manufacturer, 'edit');
    }

    #[Route('/manufacturer/{id}/delete', name: 'admin_manufacturer_delete', methods: ['POST'])]
    public  function deleteAction(Manufacturer $manufacturer): Response
    {
        try {
            $this->manufacturerService->deleteManufacturer($manufacturer);
            $this->addSuccessToast('Manufacturer deleted!', 'The manufacturer has been successfully deleted.');
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting the manufacturer: ' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_manufacturer', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/manufacturer/batch-delete', name: 'admin_manufacturer_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        try {
            $manufacturerIds = $request->request->all('id');
            
            if (empty($manufacturerIds)) {
                $this->addWarningToast('No manufacturers selected', 'Please select at least one manufacturer to delete.');
                return $this->redirectToRoute('admin_manufacturer', [], Response::HTTP_SEE_OTHER);
            }

            $result = $this->manufacturerService->batchDeleteManufacturers($manufacturerIds);

            if ($result['failure_count'] > 0) {
                $this->addWarningToast(
                    'Partial deletion!',
                    "{$result['success_count']} manufacturer(s) deleted successfully. {$result['failure_count']} manufacturer(s) could not be deleted."
                );
            } else {
                $this->addSuccessToast(
                    "Manufacturers deleted!",
                    "{$result['success_count']} manufacturer(s) have been successfully deleted."
                );
            }
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting manufacturers: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_manufacturer', [], Response::HTTP_SEE_OTHER);
    }


    private function handleManufacturerForm(Request $request, $form, $manufacturer, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile|null $image */
            $image = $form->get('image')->getData();
            if ($image) {
                $manufacturer->setImageFile($image);
            }

            if ($action === 'create') {
                $this->manufacturerService->createManufacturer($manufacturer);
            } else {
                $this->manufacturerService->updateManufacturer($manufacturer);
            }

            $this->addSuccessToast(
                $action === 'create' ? 'Manufacturer created!' : 'Manufacturer updated!',
                "The manufacturer has been successfully {$action}d."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/manufacturer/{$action}.html.twig", 'stream_success', [
                    'manufacturer' => $manufacturer
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_manufacturer', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/manufacturer/{$action}.html.twig", [
            'manufacturer' => $manufacturer,
            'form' => $form
        ]);
    }
}
