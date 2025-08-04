<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\StoreCategoryDataTableType;
use App\Entity\StoreCategory;
use App\Form\StoreCategoryType;
use App\Repository\StoreCategoryRepository;
use App\Service\StoreCategoryService;
use App\Service\MediaFileService;
use App\Traits\ToastTrait;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StoreCategoryController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly StoreCategoryRepository $storeCategoryRepository,
        private readonly StoreCategoryService $storeCategoryService,
        private readonly MediaFileService $mediaFileService
    ) {}
    
    #[Route('/store-category', name: 'admin_store_category')]
    public function index(Request $request): Response
    {
        $query = $this->storeCategoryRepository->createQueryBuilder('storeCategory');

        $datatable = $this->createNamedDataTable('categories', StoreCategoryDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/store-category/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/store-category/new', name: 'admin_store_category_new', defaults: ['title' => 'Create StoreCategory'])]
    public function createAction(Request $request): Response
    {
        $storeCategory = new StoreCategory();
        $form = $this->createForm(StoreCategoryType::class, $storeCategory, ['action' => $this->generateUrl('admin_store_category_new')]);
        return $this->handleStoreCategoryForm($request, $form, $storeCategory, 'create');
    }

    #[Route('/store-category/{id}/edit', name: 'admin_store_category_edit', defaults: ['title' => 'Edit StoreCategory'])]
    public function editAction(Request $request, StoreCategory $storeCategory): Response
    {
        $form = $this->createForm(StoreCategoryType::class, $storeCategory, [
            'action' => $this->generateUrl('admin_store_category_edit', ['id' => $storeCategory->getId()])
        ]);
        return $this->handleStoreCategoryForm($request, $form, $storeCategory, 'edit');
    }


    #[Route('/store-category/{id}/delete', name: 'admin_store_category_delete', methods: ['POST'])]
    public  function deleteAction(StoreCategory $storeCategory): Response
    {
        $this->storeCategoryService->deleteStoreCategory($storeCategory);
        $this->addSuccessToast('StoreCategory deleted!', 'The StoreCategory has been successfully deleted.');
        return $this->redirectToRoute('admin_store_category');
    }

    #[Route('/store-category/batch-delete', name: 'admin_store_category_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        $storeCategoryIds = $request->request->all('id');
        $this->storeCategoryService->batchDeleteCategories($storeCategoryIds);

        $this->addSuccessToast("Store Categories deleted!", "The categories has been successfully deleted.");
        return $this->redirectToRoute('admin_store_category');
    }

    private function handleStoreCategoryForm(Request $request, $form, $storeCategory, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // Persister/mettre à jour la catégorie
            if ($action === 'create') {
                $this->storeCategoryService->createStoreCategory($storeCategory);
            } else {
                $this->storeCategoryService->updateStoreCategory($storeCategory);
            }

            $this->addSuccessToast(
                $action === 'create' ? 'Store Category created!' : 'StoreCategory updated!',
                "The StoreCategory has been successfully {$action}d."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/store-category/{$action}.html.twig", 'stream_success', [
                    'storeCategory' => $storeCategory
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_store_category', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/store-category/{$action}.html.twig", [
            'storeCategory' => $storeCategory,
            'form' => $form
        ]);
    }
}