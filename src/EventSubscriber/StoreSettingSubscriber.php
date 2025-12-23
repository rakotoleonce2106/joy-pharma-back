<?php

namespace App\EventSubscriber;

use App\Entity\BusinessHours;
use App\Entity\StoreSetting;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * EventSubscriber for StoreSetting entity lifecycle events
 * Handles:
 * - Partial updates of BusinessHours (preserve existing hours that are not being updated)
 * - Prevent nullification of BusinessHours during PATCH operations
 * - Update existing BusinessHours instead of creating new ones
 */
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: StoreSetting::class)]
class StoreSettingSubscriber
{
    /**
     * Handle partial updates of BusinessHours before updating
     * This ensures that when only one day is updated via PATCH, other days are not affected
     */
    public function preUpdate(StoreSetting $storeSetting, PreUpdateEventArgs $event): void
    {
        $changeSet = $event->getEntityChangeSet();
        $days = ['mondayHours', 'tuesdayHours', 'wednesdayHours', 'thursdayHours', 'fridayHours', 'saturdayHours', 'sundayHours'];
        $entityManager = $event->getObjectManager();
        $uow = $entityManager->getUnitOfWork();

        foreach ($days as $day) {
            // Only process if this day is being changed
            if (!isset($changeSet[$day])) {
                continue;
            }

            $oldValue = $changeSet[$day][0]; // Existing BusinessHours from database
            $newValue = $changeSet[$day][1]; // New BusinessHours from request

            // If new value is null, keep the old value (don't allow nullification during PATCH)
            if ($newValue === null && $oldValue !== null) {
                // Revert to old value - this prevents nullification during partial updates
                $setter = 'set' . ucfirst($day);
                $storeSetting->$setter($oldValue);
                continue;
            }

            // If new value is a BusinessHours object
            if ($newValue instanceof BusinessHours) {
                $setter = 'set' . ucfirst($day);
                
                // If the new BusinessHours has an ID (from @id in request), use it directly
                if ($newValue->getId() !== null) {
                    // BusinessHours with ID - load it from database to ensure it's managed
                    $existingBusinessHours = $entityManager->find(BusinessHours::class, $newValue->getId());
                    if ($existingBusinessHours) {
                        $storeSetting->$setter($existingBusinessHours);
                    }
                    continue;
                }

                // New BusinessHours object without ID - update the existing one instead of replacing
                if ($oldValue instanceof BusinessHours) {
                    // Update existing BusinessHours properties instead of replacing the object
                    $oldValue->setOpenTime($newValue->getOpenTime());
                    $oldValue->setCloseTime($newValue->getCloseTime());
                    $oldValue->setIsClosed($newValue->isClosed());
                    
                    // Keep the old BusinessHours object (preserve the relationship)
                    $storeSetting->$setter($oldValue);
                    
                    // Ensure the BusinessHours is persisted
                    $entityManager->persist($oldValue);
                    
                    // Recompute changeSet for BusinessHours to detect changes
                    $businessHoursMetadata = $entityManager->getClassMetadata(BusinessHours::class);
                    $uow->recomputeSingleEntityChangeSet($businessHoursMetadata, $oldValue);
                } else {
                    // No existing BusinessHours - create new one
                    $entityManager->persist($newValue);
                    $storeSetting->$setter($newValue);
                }
            }
        }

        // Recompute changeSet for StoreSetting after all modifications
        $storeSettingMetadata = $entityManager->getClassMetadata(StoreSetting::class);
        $uow->recomputeSingleEntityChangeSet($storeSettingMetadata, $storeSetting);
    }
}

