<?php

namespace App\Service;

use App\Repository\MediaObjectRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for handling MediaObject operations
 * 
 * This service provides utilities for:
 * - Deleting MediaObjects and their associated files
 * - Managing MediaObject lifecycle
 * 
 * Note: IRI deserialization is handled automatically by API Platform,
 * so getMediaObjectFromIri is no longer needed in processors.
 */
class MediaObjectService
{
    public function __construct(
        private readonly MediaObjectRepository $mediaObjectRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Delete a MediaObject and its associated file
     * 
     * This will:
     * - Remove the MediaObject from database
     * - Delete the physical file (handled by VichUploader with delete_on_remove: true)
     * 
     * @param MediaObject|null $mediaObject The MediaObject to delete
     * @return void
     */
    public function deleteMediaObject(?MediaObject $mediaObject): void
    {
        if (!$mediaObject) {
            return;
        }

        // Remove from database
        // VichUploader will automatically delete the physical file if delete_on_remove is true
        $this->entityManager->remove($mediaObject);
        $this->entityManager->flush();
    }

    /**
     * Delete multiple MediaObjects by their IDs
     * 
     * @param array<int> $ids Array of MediaObject IDs to delete
     * @return void
     */
    public function deleteMediaObjectsByIds(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $mediaObjects = $this->mediaObjectRepository->findBy(['id' => $ids]);
        
        foreach ($mediaObjects as $mediaObject) {
            $this->entityManager->remove($mediaObject);
        }
        
        $this->entityManager->flush();
    }
}

