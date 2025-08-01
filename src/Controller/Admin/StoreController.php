<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\StoreDataTableType;
use App\Entity\Store;
use App\Form\StoreType;
use App\Repository\StoreRepository;
use App\Service\StoreService;
use App\Service\MediaFileService;
use App\Traits\ToastTrait;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class StoreController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly StoreRepository $StoreRepository,
        private readonly StoreService $storeService,
        private readonly MediaFileService $mediaFileService
    ) {}
    #[Route('/store', name: 'admin_store')]
    public function index(Request $request): Response
    {
        $query = $this->StoreRepository->createQueryBuilder('store');

        $datatable = $this->createNamedDataTable('Stores', StoreDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/store/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/store/new', name: 'admin_store_new', defaults: ['title' => 'Create Store'])]
    public function createAction(Request $request): Response
    {
        $store = new Store();
        $form = $this->createForm(StoreType::class, $store, ['action' => $this->generateUrl('admin_store_new')]);
        return $this->handleStoreForm($request, $form, $store, 'create');
    }

    #[Route('/store/{id}/edit', name: 'admin_store_edit', defaults: ['title' => 'Edit Store'])]
    public function editAction(Request $request, Store $store): Response
    {
        $form = $this->createForm(StoreType::class, $store, [
            'action' => $this->generateUrl('admin_store_edit', ['id' => $store->getId()])
        ]);
        return $this->handleStoreForm($request, $form, $store, 'edit');
    }

    #[Route('/store/{id}/delete', name: 'admin_store_delete', methods: ['POST'])]
    public  function deleteAction(Store $store): Response
    {
        $this->storeService->deleteStore($store);
        $this->addSuccessToast('Store deleted!', 'The Store has been successfully deleted.');
        return $this->redirectToRoute('admin_store');
    }

    #[Route('/store/batch-delete', name: 'admin_store_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        $StoreIds = $request->request->all('id');
        $this->storeService->batchDeleteStores(
            $StoreIds
        );

        $this->addSuccessToast("Stores deleted!", "The Stores have been successfully deleted.");
        return $this->redirectToRoute('admin_store');
    }


    private function handleStoreForm(Request $request, $form, $store, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($action === 'create') {
                /** @var UploadedFile|null $uploadedFile */
                $image = $form->get('image')->getData();
                if ($image) {
                    $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/store/');
                    $store->setImage($mediaFile);
                }
                /** @var UploadedFile|null $uploadedFile */
                $svg = $form->get('svg')->getData();

                if ($svg) {
                    $svgFile = $this->mediaFileService->createMediaByFile($svg, 'icons/store/');
                    $store->setSvg($svgFile);
                }
                $this->storeService->createStore($store);
            } else {
                /** @var UploadedFile|null $uploadedFile */
                $image = $form->get('image')->getData();
                if ($image) {
                    $mediaFile = $this->mediaFileService->updateMediaFileFromFile($store->getImage(), $image, 'images/store/');
                    $store->setImage($mediaFile);
                }
                /** @var UploadedFile|null $uploadedFile */
                $svg = $form->get('svg')->getData();

                if ($svg) {
                    $svgFile = $this->mediaFileService->updateMediaFileFromFile($store->getSvg(), $svg, 'icons/store/');
                    $store->setSvg($svgFile);
                }
                $this->storeService->updateStore($store);
            }


            $this->addSuccessToast(
                $action === 'create' ? 'Store created!' : 'Store updated!',
                "The Store has been successfully {$action}d."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/store/{$action}.html.twig", 'stream_success', [
                    'store' => $store
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_store', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/store/{$action}.html.twig", [
            'store' => $store,
            'form' => $form
        ]);
    }
}
