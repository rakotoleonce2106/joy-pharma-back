<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DataTable\Type\CategoryDataTableType;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\CategoryService;
use App\Traits\ToastTrait;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly EntityManagerInterface $entityManager
    ) {}
    
    #[Route('/category', name: 'admin_category')]
    public function index(Request $request): Response
    {
        $query = $this->categoryRepository->createQueryBuilder('category');

        $datatable = $this->createNamedDataTable('categories', CategoryDataTableType::class, $query);
        $datatable->handleRequest($request);

        $rootCategories = $this->categoryRepository->findRootCategories();

        return $this->render('admin/category/index.html.twig', [
            'datatable' => $datatable->createView(),
            'rootCategories' => $rootCategories,
        ]);
    }

    #[Route('/category/new', name: 'admin_category_new', defaults: ['title' => 'Create category'])]
    public function createAction(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category, ['action' => $this->generateUrl('admin_category_new')]);
        return $this->handleCreate($request, $form, $category);
    }

    #[Route('/category/{id}/edit', name: 'admin_category_edit', defaults: ['title' => 'Edit category'])]
    public function editAction(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category, [
            'action' => $this->generateUrl('admin_category_edit', ['id' => $category->getId()])
        ]);
        return $this->handleUpdate($request, $form, $category);
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

    private function handleCreate(Request $request, $form, Category $category): Response
    {
        $form->handleRequest($request);
        
        // Debug: Log form submission status
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                // Log validation errors
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addErrorToast(
                    'Validation failed!',
                    'Please check the form for errors: ' . implode(', ', $errors)
                );
            }
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'image
            /** @var UploadedFile|null $image */
            $image = $form->get('image')->getData();
            if ($image) {
                $category->setImageFile($image);
                // Si un nouveau MediaObject a été créé, le persister
                if ($category->getImage()) {
                    $this->entityManager->persist($category->getImage());
                }
            }

            // Gestion du SVG
            /** @var UploadedFile|null $svg */
            $svg = $form->get('svg')->getData();
            if ($svg) {
                $category->setSvgFile($svg);
                // Si un nouveau MediaObject a été créé, le persister
                if ($category->getSvg()) {
                    $this->entityManager->persist($category->getSvg());
                }
            }

            // Créer la catégorie (le cascade persist gérera automatiquement les MediaObject)
            $this->categoryService->createCategory($category);

            $this->addSuccessToast(
                'Category created!',
                "The category has been successfully created."
            );

            if ($request->headers->has('turbo-frame')) {
                $stream = $this->renderBlockView("admin/category/create.html.twig", 'stream_success', [
                    'category' => $category
                ]);
                $this->addFlash('stream', $stream);
            }

            return $this->redirectToRoute('admin_category', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render("admin/category/create.html.twig", [
            'category' => $category,
            'form' => $form
        ]);
    }

    private function handleUpdate(Request $request, $form, Category $category): Response
    {
        $form->handleRequest($request);
        
        // Debug: Log form submission status
        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                // Log validation errors
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                $this->addErrorToast(
                    'Validation failed!',
                    'Please check the form for errors: ' . implode(', ', $errors)
                );
            }
        }
        
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->render("admin/category/edit.html.twig", [
                'category' => $category,
                'form' => $form
            ]);
        }

        // IMPORTANT: S'assurer que la catégorie est gérée par Doctrine
        // Si elle n'est pas gérée, la recharger depuis la base de données
        $categoryId = $category->getId();
        if (!$categoryId) {
            throw new \RuntimeException('Cannot update category without ID');
        }

        if (!$this->entityManager->contains($category)) {
            $managedCategory = $this->categoryRepository->createQueryBuilder('c')
                ->leftJoin('c.image', 'img')
                ->leftJoin('c.svg', 'svg')
                ->addSelect('img', 'svg')
                ->where('c.id = :id')
                ->setParameter('id', $categoryId)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$managedCategory) {
                throw $this->createNotFoundException('Category not found');
            }

            // Copier les propriétés du formulaire vers la catégorie gérée
            $managedCategory->setName($category->getName());
            $managedCategory->setDescription($category->getDescription());
            $managedCategory->setColor($category->getColor());
            $managedCategory->setParent($category->getParent());
            $category = $managedCategory;
        }

        // Gestion de l'image
        /** @var UploadedFile|null $image */
        $image = $form->get('image')->getData();
        if ($image) {
            $category->setImageFile($image);
            // Si un nouveau MediaObject a été créé, le persister
            if ($category->getImage()) {
                $this->entityManager->persist($category->getImage());
            }
        }

        // Gestion du SVG
        /** @var UploadedFile|null $svg */
        $svg = $form->get('svg')->getData();
        if ($svg) {
            $category->setSvgFile($svg);
            // Si un nouveau MediaObject a été créé, le persister
            if ($category->getSvg()) {
                $this->entityManager->persist($category->getSvg());
            }
        }

        // Mettre à jour la catégorie (elle est déjà gérée par Doctrine)
        $this->categoryService->updateCategory($category);

        $this->addSuccessToast(
            'Category updated!',
            "The category has been successfully updated."
        );

        if ($request->headers->has('turbo-frame')) {
            $stream = $this->renderBlockView("admin/category/edit.html.twig", 'stream_success', [
                'category' => $category
            ]);
            $this->addFlash('stream', $stream);
        }

        return $this->redirectToRoute('admin_category', status: Response::HTTP_SEE_OTHER);
    }
}