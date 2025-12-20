<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\BrandInput;
use App\Entity\Brand;
use App\Entity\MediaObject;
use App\Repository\BrandRepository;
use App\Service\BrandService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BrandProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly BrandService $brandService,
        private readonly BrandRepository $brandRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Brand
    {
        if (!$data instanceof BrandInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $brand = $this->brandRepository->find($uriVariables['id']);
            if (!$brand) {
                throw new NotFoundHttpException('Brand not found');
            }
        } else {
            $brand = new Brand();
        }

        $brand->setName($data->name);

        // Handle image file upload from multipart request
        $request = $this->requestStack->getCurrentRequest();
        $needsFlush = false;
        
        if ($request) {
            // Try multiple possible field names for compatibility
            $imageFile = null;
            if ($request->files->has('image')) {
                $imageFile = $request->files->get('image');
            } elseif ($request->files->has('imageFile')) {
                $imageFile = $request->files->get('imageFile');
            } elseif ($data->image instanceof UploadedFile) {
                $imageFile = $data->image;
            }

            if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                if ($brand->getImage()) {
                    // Update existing image
                    $existingImage = $brand->getImage();
                    $existingImage->setFile($imageFile);
                    $existingImage->setMapping('media_object');
                    if (!$this->entityManager->contains($existingImage)) {
                        $this->entityManager->persist($existingImage);
                    }
                    $needsFlush = true;
                } else {
                    // Create new MediaObject for image
                    $imageMediaObject = new MediaObject();
                    $imageMediaObject->setFile($imageFile);
                    $imageMediaObject->setMapping('media_object');
                    $this->entityManager->persist($imageMediaObject);
                    $brand->setImage($imageMediaObject);
                    $needsFlush = true;
                }
            }
        }

        // Flush MediaObjects if any were created/updated so VichUploader can process the uploads
        if ($needsFlush) {
            $this->entityManager->flush();
        }

        if ($isUpdate) {
            $this->brandService->updateBrand($brand);
        } else {
            $this->brandService->createBrand($brand);
        }

        return $brand;
    }
}

