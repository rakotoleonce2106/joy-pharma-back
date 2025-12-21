<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\CategoryInput;
use App\Entity\Category;
use App\Entity\MediaObject;
use App\Repository\CategoryRepository;
use App\Service\CategoryService;
use App\Service\MediaObjectService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaObjectService $mediaObjectService
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
        // For update: only update name if provided, otherwise keep existing name
        if ($isUpdate) {
            if ($data->name !== null && $data->name !== '') {
                $category->setName($data->name);
            }
            // Only update description if provided
            if ($data->description !== null) {
                $category->setDescription($data->description);
            }
            // Only update color if provided
            if ($data->color !== null) {
                $category->setColor($data->color);
            }
        } else {
            // For create: all fields are set (name is required via validation)
            $category->setName($data->name);
            $category->setDescription($data->description);
            $category->setColor($data->color);
        }
        
        // Parent can be updated in both cases
        $category->setParent($parent);

        $needsFlush = false;

        // Handle image: API Platform automatically deserializes IRI to MediaObject entity (JSON-LD)
        if ($data->image instanceof MediaObject) {
            // Store previous image ID for deletion if it exists and is different
            $previousImageId = $category->getImage()?->getId();
            $data->image->setMapping('category_images');
            $category->setImage($data->image);
            $needsFlush = true;
            
            // Delete previous image if it was replaced
            if ($previousImageId && $previousImageId !== $category->getImage()?->getId()) {
                $this->mediaObjectService->deleteMediaObjectsByIds([$previousImageId]);
            }
        }

        // Handle icon: API Platform automatically deserializes IRI to MediaObject entity (JSON-LD)
        if ($data->icon instanceof MediaObject) {
            // Store previous icon ID for deletion if it exists and is different
            $previousIconId = $category->getSvg()?->getId();
            $data->icon->setMapping('category_icons');
            $category->setSvg($data->icon);
            $needsFlush = true;
            
            // Delete previous icon if it was replaced
            if ($previousIconId && $previousIconId !== $category->getSvg()?->getId()) {
                $this->mediaObjectService->deleteMediaObjectsByIds([$previousIconId]);
            }
        }

        // Flush MediaObjects if any were created/updated so VichUploader can process the uploads
        if ($needsFlush) {
            $this->entityManager->flush();
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

