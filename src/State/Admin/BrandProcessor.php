<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Admin\BrandInput;
use App\Entity\Brand;
use App\Entity\MediaObject;
use App\Repository\BrandRepository;
use App\Service\BrandService;
use App\Service\MediaObjectService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BrandProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly BrandService $brandService,
        private readonly BrandRepository $brandRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MediaObjectService $mediaObjectService
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

        $needsFlush = false;

        // Handle image: API Platform automatically deserializes IRI to MediaObject entity (JSON-LD)
        if ($data->image instanceof MediaObject) {
            // Store previous image ID for deletion if it exists and is different
            $previousImageId = $brand->getImage()?->getId();
            $data->image->setMapping('media_object');
            $brand->setImage($data->image);
            $needsFlush = true;
            
            // Delete previous image if it was replaced
            if ($previousImageId && $previousImageId !== $brand->getImage()?->getId()) {
                $this->mediaObjectService->deleteMediaObjectsByIds([$previousImageId]);
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

