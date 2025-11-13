<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\CategoryInput;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\CategoryService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CategoryRepository $categoryRepository
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Category
    {
        if (!$data instanceof CategoryInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $category = $this->categoryRepository->find($uriVariables['id']);
            if (!$category) {
                throw new NotFoundHttpException('Category not found');
            }
        } else {
            $category = new Category();
        }

        $category->setName($data->name);
        $category->setDescription($data->description);
        $category->setColor($data->color);

        // Handle parent category
        if ($data->parent) {
            $parent = $this->categoryRepository->find($data->parent);
            if (!$parent) {
                throw new BadRequestHttpException('Parent category not found');
            }
            $category->setParent($parent);
        } else {
            $category->setParent(null);
        }

        if ($isUpdate) {
            $this->categoryService->updateCategory($category);
        } else {
            $this->categoryService->createCategory($category);
        }

        return $category;
    }
}

