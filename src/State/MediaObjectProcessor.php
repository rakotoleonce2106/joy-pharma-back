<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use App\Repository\MediaObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

final class MediaObjectProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly MediaObjectRepository $mediaObjectRepository
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MediaObject
    {
        $request = $this->requestStack->getCurrentRequest();
        
        // Check if an ID is provided to update existing MediaObject
        $mediaObjectId = null;
        if ($request && $request->request->has('id')) {
            $mediaObjectId = $request->request->get('id');
            // Convert to integer if it's a string
            if (is_string($mediaObjectId) && is_numeric($mediaObjectId)) {
                $mediaObjectId = (int) $mediaObjectId;
            } elseif (!is_int($mediaObjectId)) {
                $mediaObjectId = null;
            }
        }
        
        // If ID is provided, load existing MediaObject or create new one
        if ($mediaObjectId) {
            $existingMediaObject = $this->mediaObjectRepository->find($mediaObjectId);
            if ($existingMediaObject) {
                $data = $existingMediaObject;
            }
            // If ID provided but doesn't exist, continue with new MediaObject creation
        }
        
        // Get file from request (multipart/form-data) or from data object
        $file = null;
        if ($request) {
            // Try multiple possible field names for compatibility
            if ($request->files->has('file')) {
                $file = $request->files->get('file');
            } elseif ($request->files->has('fileFile')) {
                $file = $request->files->get('fileFile');
            } elseif ($data->getFile() instanceof UploadedFile) {
                $file = $data->getFile();
            }
        } else {
            // Fallback to data object if no request
            $file = $data->getFile() instanceof UploadedFile ? $data->getFile() : null;
        }

        // Handle file upload
        if ($file instanceof UploadedFile && $file->isValid()) {
            $data->setFile($file);
            
            // Set mapping if provided in request
            if ($request && $request->request->has('mapping')) {
                $mapping = $request->request->get('mapping');
                if (in_array($mapping, [
                    'media_object',
                    'category_images',
                    'category_icons',
                    'product_images',
                    'brand_images',
                    'manufacturer_images',
                    'user_images',
                    'store_images'
                ], true)) {
                    $data->setMapping($mapping);
                }
            }
        }

        // Persist and flush to trigger VichUploader
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        // Set content URL after file is uploaded
        if ($data->getFilePath()) {
            $mapping = $data->getMapping() ?? 'media_object';
            $prefix = match($mapping) {
                'category_images' => '/images/categories/',
                'category_icons' => '/icons/categories/',
                'product_images' => '/images/products/',
                'brand_images' => '/images/brands/',
                'manufacturer_images' => '/images/manufacturers/',
                'user_images' => '/images/users/',
                'store_images' => '/images/stores/',
                default => '/media/',
            };
            $data->setContentUrl($prefix . $data->getFilePath());
        }

        return $data;
    }
}

