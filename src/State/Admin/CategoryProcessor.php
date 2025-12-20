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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CategoryProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack
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
        $request = $this->requestStack->getCurrentRequest();

        // Get files directly from request (more reliable than DTO for multipart)
        $imageFile = null;
        $iconFile = null;
        
        if ($request) {
            // Try multiple possible field names
            $imageFile = $request->files->get('image') 
                      ?? $request->files->get('imageFile')
                      ?? $data->image;
            
            $iconFile = $request->files->get('icon')
                     ?? $request->files->get('iconFile')
                     ?? $request->files->get('svg')
                     ?? $data->icon;
        } else {
            // Fallback to DTO if no request
            $imageFile = $data->image;
            $iconFile = $data->icon;
        }

        // Handle image file upload
        if ($imageFile instanceof UploadedFile) {
            if ($category->getImage()) {
                // Update existing image
                $existingImage = $category->getImage();
                $existingImage->setFile($imageFile);
                // Ensure MediaObject is managed
                if (!$this->entityManager->contains($existingImage)) {
                    $this->entityManager->persist($existingImage);
                }
                $needsFlush = true;
            } else {
                // Create new MediaObject for image
                $imageMediaObject = new MediaObject();
                $imageMediaObject->setFile($imageFile);
                $this->entityManager->persist($imageMediaObject);
                $category->setImage($imageMediaObject);
                $needsFlush = true;
            }
        }

        // Handle icon/SVG file upload
        if ($iconFile instanceof UploadedFile) {
            if ($category->getSvg()) {
                // Update existing icon
                $existingSvg = $category->getSvg();
                $existingSvg->setFile($iconFile);
                // Ensure MediaObject is managed
                if (!$this->entityManager->contains($existingSvg)) {
                    $this->entityManager->persist($existingSvg);
                }
                $needsFlush = true;
            } else {
                // Create new MediaObject for icon
                $iconMediaObject = new MediaObject();
                $iconMediaObject->setFile($iconFile);
                $this->entityManager->persist($iconMediaObject);
                $category->setSvg($iconMediaObject);
                $needsFlush = true;
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

