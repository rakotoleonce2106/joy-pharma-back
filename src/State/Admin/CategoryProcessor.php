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

        // Debug: Log what's in the request
        if ($request) {
            error_log('[CategoryProcessor] Request files: ' . json_encode(array_keys($request->files->all())));
            error_log('[CategoryProcessor] Request data: ' . json_encode(array_keys($request->request->all())));
        }

        // Get files directly from request (more reliable than DTO for multipart)
        // This works for both POST and PUT operations
        $imageFile = null;
        $iconFile = null;
        
        if ($request) {
            // Get files from request->files (works for both POST and PUT)
            // Try multiple possible field names for compatibility
            if ($request->files->has('image')) {
                $imageFile = $request->files->get('image');
                error_log('[CategoryProcessor] Found image file via "image" field');
            } elseif ($request->files->has('imageFile')) {
                $imageFile = $request->files->get('imageFile');
                error_log('[CategoryProcessor] Found image file via "imageFile" field');
            } elseif ($data->image instanceof UploadedFile) {
                $imageFile = $data->image;
                error_log('[CategoryProcessor] Found image file via DTO');
            } else {
                error_log('[CategoryProcessor] No image file found');
            }
            
            if ($request->files->has('icon')) {
                $iconFile = $request->files->get('icon');
                error_log('[CategoryProcessor] Found icon file via "icon" field');
            } elseif ($request->files->has('iconFile')) {
                $iconFile = $request->files->get('iconFile');
                error_log('[CategoryProcessor] Found icon file via "iconFile" field');
            } elseif ($request->files->has('svg')) {
                $iconFile = $request->files->get('svg');
                error_log('[CategoryProcessor] Found icon file via "svg" field');
            } elseif ($data->icon instanceof UploadedFile) {
                $iconFile = $data->icon;
                error_log('[CategoryProcessor] Found icon file via DTO');
            } else {
                error_log('[CategoryProcessor] No icon file found');
            }
        } else {
            error_log('[CategoryProcessor] No request object available');
            // Fallback to DTO if no request (shouldn't happen in normal flow)
            $imageFile = $data->image instanceof UploadedFile ? $data->image : null;
            $iconFile = $data->icon instanceof UploadedFile ? $data->icon : null;
        }

        // Handle image file upload
        if ($imageFile instanceof UploadedFile) {
            error_log('[CategoryProcessor] Processing image file: ' . $imageFile->getClientOriginalName());
            if ($category->getImage()) {
                error_log('[CategoryProcessor] Updating existing image MediaObject');
                // Update existing image
                $existingImage = $category->getImage();
                $existingImage->setFile($imageFile);
                // Ensure MediaObject is managed
                if (!$this->entityManager->contains($existingImage)) {
                    $this->entityManager->persist($existingImage);
                    error_log('[CategoryProcessor] Persisted existing image MediaObject');
                }
                $needsFlush = true;
            } else {
                error_log('[CategoryProcessor] Creating new image MediaObject');
                // Create new MediaObject for image
                $imageMediaObject = new MediaObject();
                $imageMediaObject->setFile($imageFile);
                $this->entityManager->persist($imageMediaObject);
                $category->setImage($imageMediaObject);
                $needsFlush = true;
            }
        } else {
            error_log('[CategoryProcessor] Image file is not an UploadedFile instance: ' . gettype($imageFile));
        }

        // Handle icon/SVG file upload
        if ($iconFile instanceof UploadedFile) {
            error_log('[CategoryProcessor] Processing icon file: ' . $iconFile->getClientOriginalName());
            if ($category->getSvg()) {
                error_log('[CategoryProcessor] Updating existing icon MediaObject');
                // Update existing icon
                $existingSvg = $category->getSvg();
                $existingSvg->setFile($iconFile);
                // Ensure MediaObject is managed
                if (!$this->entityManager->contains($existingSvg)) {
                    $this->entityManager->persist($existingSvg);
                    error_log('[CategoryProcessor] Persisted existing icon MediaObject');
                }
                $needsFlush = true;
            } else {
                error_log('[CategoryProcessor] Creating new icon MediaObject');
                // Create new MediaObject for icon
                $iconMediaObject = new MediaObject();
                $iconMediaObject->setFile($iconFile);
                $this->entityManager->persist($iconMediaObject);
                $category->setSvg($iconMediaObject);
                $needsFlush = true;
            }
        } else {
            error_log('[CategoryProcessor] Icon file is not an UploadedFile instance: ' . gettype($iconFile));
        }

        // Flush MediaObjects if any were created/updated so VichUploader can process the uploads
        if ($needsFlush) {
            error_log('[CategoryProcessor] Flushing MediaObjects for VichUploader to process');
            $this->entityManager->flush();
            error_log('[CategoryProcessor] MediaObjects flushed successfully');
        } else {
            error_log('[CategoryProcessor] No files to flush');
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

