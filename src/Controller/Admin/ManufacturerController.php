<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\ManufacturerDataTableType;
use App\Entity\Manufacturer;
use App\Form\ManufacturerType;
use App\Repository\ManufacturerRepository;
use App\Service\ManufacturerService;
use App\Service\MediaFileService;
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
        private readonly ManufacturerService $manufacturerService,
        private readonly MediaFileService $mediaFileService
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
        $this->manufacturerService->deleteManufacturer($manufacturer);
        $this->addSuccessToast('Manufacturer deleted!', 'The manufacturer has been successfully deleted.');
        return $this->redirectToRoute('admin_manufacturer');
    }

    #[Route('/manufacturer/batch-delete', name: 'admin_manufacturer_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        $manufacturerIds = $request->request->all('id');
        $this->manufacturerService->batchDeleteManufacturers(
            $manufacturerIds
        );

        $this->addSuccessToast("Manufacturers deleted!", "The manufacturers have been successfully deleted.");
        return $this->redirectToRoute('admin_manufacturer');
    }


    private function handleManufacturerForm(Request $request, $form, $manufacturer, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($action === 'create') {
                /** @var UploadedFile|null $uploadedFile */
                $image = $form->get('image')->getData();
                if ($image) {
                    $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/manufacturer/');
                    $manufacturer->setImage($mediaFile);
                }
               
                $this->manufacturerService->createManufacturer($manufacturer);
            } else {
                /** @var UploadedFile|null $uploadedFile */
                $image = $form->get('image')->getData();
                if ($image) {
                    $mediaFile = $this->mediaFileService->updateMediaFileFromFile($manufacturer->getImage(), $image, 'images/manufacturer/');
                    $manufacturer->setImage($mediaFile);
                }
                
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
