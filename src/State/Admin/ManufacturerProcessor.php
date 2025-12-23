<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\ManufacturerInput;
use App\Entity\Manufacturer;
use App\Entity\MediaObject;
use App\Repository\ManufacturerRepository;
use App\Service\ManufacturerService;
use App\Service\MediaObjectService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ManufacturerProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ManufacturerService $manufacturerService,
        private readonly ManufacturerRepository $manufacturerRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaObjectService $mediaObjectService
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

        $needsFlush = false;

        // Handle image: API Platform automatically deserializes IRI to MediaObject entity (JSON-LD)
        if ($data->image instanceof MediaObject) {
            // Store previous image ID for deletion if it exists and is different
            $previousImageId = $manufacturer->getImage()?->getId();
            $data->image->setMapping('media_object');
            $manufacturer->setImage($data->image);
            $needsFlush = true;
            
            // Delete previous image if it was replaced
            if ($previousImageId && $previousImageId !== $manufacturer->getImage()?->getId()) {
                $this->mediaObjectService->deleteMediaObjectsByIds([$previousImageId]);
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

