<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\CategoryDataTableType;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\CategoryService;
use App\Service\MediaFileService;
use App\Traits\ToastTrait;
use Kreyu\Bundle\DataTableBundle\DataTableFactoryAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CategoryController extends AbstractController
{
    use DataTableFactoryAwareTrait;
    use ToastTrait;

    public  function __construct(
        private  readonly CategoryRepository $categoryRepository,
        private readonly CategoryService $categoryService,
        private readonly MediaFileService $mediaFileService
    ) {}
    #[Route('/category', name: 'admin_category')]
    public function index(Request $request): Response
    {
        $query = $this->categoryRepository->createQueryBuilder('category');

        $datatable = $this->createNamedDataTable('categories', CategoryDataTableType::class, $query);
        $datatable->handleRequest($request);

        return $this->render('admin/category/index.html.twig', [
            'datatable' => $datatable->createView()
        ]);
    }

    #[Route('/category/new', name: 'admin_category_new', defaults: ['title' => 'Create category'])]
    public function createAction(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category, ['action' => $this->generateUrl('admin_category_new')]);
        return $this->handleCategoryForm($request, $form, $category, 'create');
    }

    #[Route('/category/{id}/edit', name: 'admin_category_edit', defaults: ['title' => 'Edit category'])]
    public function editAction(Request $request, Category $category): Response
    {

        $form = $this->createForm(CategoryType::class, $category, [
            'action' => $this->generateUrl('admin_category_edit', ['id' => $category->getId()])
        ]);
        return $this->handleCategoryForm($request, $form, $category, 'edit');
    }

    #[Route('/category/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public  function deleteAction(Category $category): Response
    {
        $this->categoryService->deleteCategory($category);
        $this->addSuccessToast('Category deleted!', 'The category has been successfully deleted.');
        return $this->redirectToRoute('admin_category');
    }

    #[Route('/category/batch-delete', name: 'admin_category_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        $categoryIds = $request->request->all('id');
        $this->categoryService->batchDeleteCategories(
            $categoryIds
        );

        $this->addSuccessToast("Categories deleted!", "The categories has been successfully deleted.");
        return $this->redirectToRoute('admin_category');
    }


    private function handleCategoryForm(Request $request, $form, $category, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            if ($action === 'create') {
                /** @var UploadedFile|null $uploadedFile */
                $image = $form->get('image')->getData();
                if ($image) {
                    $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/category/');
                    $category->setImage($mediaFile);
                }
                /** @var UploadedFile|null $uploadedFile */
                $svg = $form->get('svg')->getData();

                if ($svg) {
                    $svgFile = $this->mediaFileService->createMediaByFile($svg, 'icons/category/');
                    $category->setSvg($svgFile);
                }
                $this->categoryService->createCategory($category);
            } else {
                /** @var UploadedFile|null $uploadedFile */
                $image = $form->get('image')->getData();
                if ($image) {
                    $mediaFile = $this->mediaFileService->updateMediaFileFromFile($category->getImage(), $image, 'images/category/');
                    $category->setImage($mediaFile);
                }
                /** @var UploadedFile|null $uploadedFile */
                $svg = $form->get('svg')->getData();

                if ($svg) {
                    $svgFile = $this->mediaFileService->updateMediaFileFromFile($category->getSvg(), $svg, 'icons/category/');
                    $category->setSvg($svgFile);
                }
                $this->categoryService->updateCategory($category);
            }


            $this->addSuccessToast(
                $action === 'create' ? 'Product created!' : 'Product updated!',
                "The product has been successfully {$action}d."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/category/{$action}.html.twig", 'stream_success', [
                    'category' => $category
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_category', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/category/{$action}.html.twig", [
            'category' => $category,
            'form' => $form
        ]);
    }
}
