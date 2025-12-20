<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\CategoryInput;
use App\Entity\Category;
use App\Entity\MediaObject;
use App\Repository\CategoryRepository;
use App\Service\CategoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Category
    {
        if (!$data instanceof CategoryInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        // Handle parent category first (needed for existence check)
        $parent = null;
        // Handle parent: can be null, empty string, or integer
        if ($data->parent !== null && $data->parent !== '' && $data->parent !== 'null') {
            $parentId = is_numeric($data->parent) ? (int)$data->parent : null;
            if ($parentId) {
                $parent = $this->categoryRepository->find($parentId);
                if (!$parent) {
                    throw new BadRequestHttpException('Parent category not found');
                }
            }
        }

        if ($isUpdate) {
            // Update existing category
            $category = $this->categoryRepository->find($uriVariables['id']);
            if (!$category) {
                throw new NotFoundHttpException('Category not found');
            }
            $categoryExists = true;
        } else {
            // Check if category already exists (by name and parent)
            $category = $this->categoryRepository->findOneBy([
                'name' => $data->name,
                'parent' => $parent,
            ]);

            // If category doesn't exist, create a new one
            $categoryExists = $category !== null;
            if (!$category) {
                $category = new Category();
            }
        }

        // Update category properties
        $category->setName($data->name);
        $category->setDescription($data->description);
        $category->setColor($data->color);
        $category->setParent($parent);

        // Handle image file upload
        if ($data->image) {
            if ($category->getImage()) {
                // Update existing image
                $category->getImage()->setFile($data->image);
            } else {
                // Create new MediaObject for image
                $imageMediaObject = new MediaObject();
                $imageMediaObject->setFile($data->image);
                $this->entityManager->persist($imageMediaObject);
                $category->setImage($imageMediaObject);
            }
        }

        // Handle icon/SVG file upload
        if ($data->icon) {
            if ($category->getSvg()) {
                // Update existing icon
                $category->getSvg()->setFile($data->icon);
            } else {
                // Create new MediaObject for icon
                $iconMediaObject = new MediaObject();
                $iconMediaObject->setFile($data->icon);
                $this->entityManager->persist($iconMediaObject);
                $category->setSvg($iconMediaObject);
            }
        }

        // Use appropriate method based on whether category already exists
        if ($isUpdate || $categoryExists) {
            $this->categoryService->updateCategory($category);
        } else {
            $this->categoryService->createCategory($category);
        }

        return $category;
    }
}

