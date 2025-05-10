<?php

namespace App\Service;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;



readonly class CategoryService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private CategoryRepository $categoryRepository
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
        $this->manager->remove($category);
        $this->manager->flush();
    }

    public function batchDeleteCategories(array $categoryIds): void
    {


        foreach ($categoryIds as $id) {
            $category = $this->categoryRepository->find($id);
            if ($category) {
                $this->deleteCategory($category);

            }
        }



    }
}
