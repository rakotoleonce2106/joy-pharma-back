<?php

namespace App\Service;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;


readonly class CategoryService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository
    ) {
    }

    public function createCategory(Category $category): void
    {
        $this->manager->persist($category);
        $this->manager->flush();
    }


    public function getOrCreateCategoryByPath(array $path): array
    {
        $parent = null;
        $categories = [];

        foreach ($path as $name) {
            // Find category by name and parent
            $category = $this->categoryRepository->findOneBy([
                'name' => $name,
                'parent' => $parent,
            ]);

            if (!$category) {
                $category = new Category();
                $category->setName($name);
                $category->setParent($parent);
                $this->manager->persist($category);
            }

            // Prepare for next level
            $parent = $category;

            // Correct way to add into array
            $categories[] = $category;
        }

        return $categories;
    }

    public function findParentCategories(): array
    {
      return  $this->categoryRepository->findRootCategories();
    }

    public function updateCategory(Category $category): void
    {
        $this->manager->flush();
    }


    public function findByName(String $name): ?Category
    {
        return $this->manager->getRepository(Category::class)
            ->findOneBy(['name' => $name]);
    }

    public function deleteCategory(Category $category): void
    {
        // Delete category and all its children recursively
        $this->deleteCategoryRecursively($category);
        $this->manager->flush();
    }

    public function batchDeleteCategories(array $categoryIds): array
    {
        $successCount = 0;
        $failureCount = 0;

        if (empty($categoryIds)) {
            return [
                'success_count' => 0,
                'failure_count' => 0
            ];
        }

        // Load all categories first
        $categories = [];
        foreach ($categoryIds as $id) {
            $category = $this->categoryRepository->find($id);
            if ($category) {
                $categories[] = $category;
            } else {
                $failureCount++;
            }
        }

        // Sort categories: delete children first (categories with parent), then parents
        // This prevents foreign key constraint violations
        usort($categories, function($a, $b) {
            $aHasParent = $a->getParent() !== null;
            $bHasParent = $b->getParent() !== null;
            
            // Children (with parent) come first
            if ($aHasParent && !$bHasParent) {
                return -1;
            }
            if (!$aHasParent && $bHasParent) {
                return 1;
            }
            return 0;
        });

        // Delete categories recursively (children first)
        foreach ($categories as $category) {
            try {
                // First, delete all child categories recursively
                $this->deleteCategoryRecursively($category);
                $successCount++;
            } catch (\Exception $e) {
                $failureCount++;
                // Log the error but continue with other categories
                error_log('Failed to delete category with id ' . $category->getId() . ': ' . $e->getMessage());
            }
        }

        // Flush once at the end for better performance
        try {
            $this->manager->flush();
        } catch (\Exception $e) {
            error_log('Failed to flush category deletions: ' . $e->getMessage());
            throw $e;
        }

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount
        ];
    }

    /**
     * Delete a category and all its children recursively
     */
    private function deleteCategoryRecursively(Category $category): void
    {
        // First, load and delete all child categories
        // Use repository to ensure we get all children even if collection is not initialized
        $children = $this->categoryRepository->findBy(['parent' => $category]);
        
        foreach ($children as $childCategory) {
            $this->deleteCategoryRecursively($childCategory);
        }

        // Remove all product associations (ManyToMany relation)
        // Use query builder to get all products without limit
        $products = $this->manager->getRepository(\App\Entity\Product::class)
            ->createQueryBuilder('p')
            ->innerJoin('p.category', 'c')
            ->where('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId())
            ->getQuery()
            ->getResult();
        
        foreach ($products as $product) {
            $product->removeCategory($category);
        }

        // Then remove the category itself
        $this->manager->remove($category);
    }
}
