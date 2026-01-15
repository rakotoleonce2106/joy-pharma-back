<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MediaObject;
use App\Repository\MediaObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Handler\UploadHandler;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class MediaObjectProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly MediaObjectRepository $mediaObjectRepository,
        private readonly UploadHandler $uploadHandler,
        private readonly PropertyMappingFactory $propertyMappingFactory,
        private readonly ParameterBagInterface $parameterBag
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
            // Set mapping if provided in request
            $mapping = 'media_object'; // Default mapping
            if ($request && $request->request->has('mapping')) {
                $requestMapping = $request->request->get('mapping');
                if (in_array($requestMapping, [
                    'media_object',
                    'category_images',
                    'category_icons',
                    'product_images',
                    'brand_images',
                    'manufacturer_images',
                    'user_images',
                    'store_images',
                    'deliver_documents'
                ], true)) {
                    $mapping = $requestMapping;
                }
            }
            
            // Set the mapping in the entity
            $data->setMapping($mapping);
            
            // Set the file
            $data->setFile($file);
            
            // Persist first to get the entity ID
            $this->entityManager->persist($data);
            $this->entityManager->flush();
            
            // Upload the file using the correct mapping
            // UploadHandler::upload() expects the field name as second parameter, not PropertyMapping
            $this->uploadHandler->upload($data, 'file');
            
            // If mapping is different from 'media_object', move the file to the correct directory
            if ($mapping !== 'media_object' && $data->getFilePath()) {
                $kernelProjectDir = $this->parameterBag->get('kernel.project_dir');
                
                // Get source and destination directories from vich_uploader configuration
                $sourceDir = $kernelProjectDir . '/public/media';
                $destinationDirs = [
                    'category_images' => $kernelProjectDir . '/public/images/categories',
                    'category_icons' => $kernelProjectDir . '/public/icons/categories',
                    'product_images' => $kernelProjectDir . '/public/images/products',
                    'brand_images' => $kernelProjectDir . '/public/images/brands',
                    'manufacturer_images' => $kernelProjectDir . '/public/images/manufacturers',
                    'user_images' => $kernelProjectDir . '/public/images/users',
                    'store_images' => $kernelProjectDir . '/public/images/stores',
                    'deliver_documents' => $kernelProjectDir . '/public/uploads/deliver',
                ];
                
                $destinationDir = $destinationDirs[$mapping] ?? null;
                
                if ($destinationDir && is_dir($sourceDir) && file_exists($sourceDir . '/' . $data->getFilePath())) {
                    // Ensure destination directory exists
                    if (!is_dir($destinationDir)) {
                        mkdir($destinationDir, 0755, true);
                    }
                    
                    // Move the file
                    $sourceFile = $sourceDir . '/' . $data->getFilePath();
                    $destinationFile = $destinationDir . '/' . $data->getFilePath();
                    
                    if (file_exists($sourceFile)) {
                        rename($sourceFile, $destinationFile);
                    }
                }
            }
            
            // Flush again to save the filePath
            $this->entityManager->flush();
        } else {
            // No file upload, just persist
            $this->entityManager->persist($data);
            $this->entityManager->flush();
        }

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

