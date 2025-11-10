<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\CategoryDataTableType;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\CategoryService;
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
        private readonly CategoryService $categoryService
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
        try {
            $this->categoryService->deleteCategory($category);
            $this->addSuccessToast('Category deleted!', 'The category has been successfully deleted.');
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting the category: ' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_category', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/category/batch-delete', name: 'admin_category_batch_delete', methods: ['POST'])]
    public function batchDeleteAction(Request $request): Response
    {
        try {
            $categoryIds = $request->request->all('id');
            
            if (empty($categoryIds)) {
                $this->addWarningToast('No categories selected', 'Please select at least one category to delete.');
                return $this->redirectToRoute('admin_category', [], Response::HTTP_SEE_OTHER);
            }

            $result = $this->categoryService->batchDeleteCategories($categoryIds);

            if ($result['failure_count'] > 0) {
                $this->addWarningToast(
                    'Partial deletion!',
                    "{$result['success_count']} category(ies) deleted successfully. {$result['failure_count']} category(ies) could not be deleted."
                );
            } else {
                $this->addSuccessToast(
                    "Categories deleted!",
                    "{$result['success_count']} category(ies) have been successfully deleted."
                );
            }
        } catch (\Exception $e) {
            $this->addErrorToast('Delete failed!', 'An error occurred while deleting categories: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_category', [], Response::HTTP_SEE_OTHER);
    }

    private function handleCategoryForm(Request $request, $form, $category, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // Gestion de l'image
            /** @var UploadedFile|null $image */
            $image = $form->get('image')->getData();
            if ($image) {
                $category->setImageFile($image);
            }

            // Gestion du SVG
            /** @var UploadedFile|null $svg */
            $svg = $form->get('svg')->getData();
            if ($svg) {
                $category->setSvgFile($svg);
            }

            // Persister/mettre à jour la catégorie
            if ($action === 'create') {
                $this->categoryService->createCategory($category);
            } else {
                $this->categoryService->updateCategory($category);
            }

            $this->addSuccessToast(
                $action === 'create' ? 'Category created!' : 'Category updated!',
                "The category has been successfully {$action}d."
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