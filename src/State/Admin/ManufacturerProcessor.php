<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\ManufacturerInput;
use App\Entity\Manufacturer;
use App\Entity\MediaObject;
use App\Repository\ManufacturerRepository;
use App\Service\ManufacturerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ManufacturerProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ManufacturerService $manufacturerService,
        private readonly ManufacturerRepository $manufacturerRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Manufacturer
    {
        if (!$data instanceof ManufacturerInput) {
            throw new BadRequestHttpException('Invalid input data');
        }

        $isUpdate = isset($uriVariables['id']);
        
        if ($isUpdate) {
            $manufacturer = $this->manufacturerRepository->find($uriVariables['id']);
            if (!$manufacturer) {
                throw new NotFoundHttpException('Manufacturer not found');
            }
        } else {
            $manufacturer = new Manufacturer();
        }

        $manufacturer->setName($data->name);
        $manufacturer->setDescription($data->description);

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
                if ($manufacturer->getImage()) {
                    // Update existing image
                    $existingImage = $manufacturer->getImage();
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
                    $manufacturer->setImage($imageMediaObject);
                    $needsFlush = true;
                }
            }
        }

        // Flush MediaObjects if any were created/updated so VichUploader can process the uploads
        if ($needsFlush) {
            $this->entityManager->flush();
        }

        if ($isUpdate) {
            $this->manufacturerService->updateManufacturer($manufacturer);
        } else {
            $this->manufacturerService->createManufacturer($manufacturer);
        }

        return $manufacturer;
    }
}

