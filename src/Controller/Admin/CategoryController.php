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
        $this->categoryService->batchDeleteCategories($categoryIds);

        $this->addSuccessToast("Categories deleted!", "The categories has been successfully deleted.");
        return $this->redirectToRoute('admin_category');
    }

    private function handleCategoryForm(Request $request, $form, $category, string $action): Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // Gestion de l'image
            /** @var UploadedFile|null $image */
            $image = $form->get('image')->getData();
            if ($image) {
                if ($action === 'create') {
                    // Création d'un nouveau MediaFile pour une nouvelle catégorie
                    $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/category/');
                    $category->setImage($mediaFile);
                } else {
                    // Mise à jour : on met à jour le MediaFile existant ou on en crée un nouveau
                    $existingMediaFile = $category->getImage();
                    if ($existingMediaFile) {
                        // Mettre à jour le MediaFile existant
                         $this->mediaFileService->updateMediaFileFromFile($existingMediaFile, $image, 'images/category/');
                    } else {
                        // Créer un nouveau MediaFile si aucun n'existait
                        $mediaFile = $this->mediaFileService->createMediaByFile($image, 'images/category/');
                        $category->setImage($mediaFile);
                    }
                }
            }

            // Gestion du SVG
            /** @var UploadedFile|null $svg */
            $svg = $form->get('svg')->getData();
            if ($svg) {
                if ($action === 'create') {
                    // Création d'un nouveau MediaFile pour une nouvelle catégorie
                    $svgFile = $this->mediaFileService->createMediaByFile($svg, 'icons/category/');
                    $category->setSvg($svgFile);
                } else {
                    // Mise à jour : on met à jour le MediaFile existant ou on en crée un nouveau
                    $existingSvgFile = $category->getSvg();
                    if ($existingSvgFile) {
                        // Mettre à jour le MediaFile existant
                        $this->mediaFileService->updateMediaFileFromFile($existingSvgFile, $svg, 'icons/category/');
                    } else {
                        // Créer un nouveau MediaFile si aucun n'existait
                        $svgFile = $this->mediaFileService->createMediaByFile($svg, 'icons/category/');
                        $category->setSvg($svgFile);
                    }
                }
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