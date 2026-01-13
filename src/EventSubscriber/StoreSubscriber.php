<?php

namespace App\EventSubscriber;

use App\Entity\BusinessHours;
use App\Entity\MediaObject;
use App\Entity\Store;
use App\Entity\StoreSetting;
use App\Entity\User;
use App\Entity\OrderItem;
use App\Entity\QrScanLog;
use App\Service\MediaObjectService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

/**
 * EventSubscriber for Store entity lifecycle events
 * Handles:
 * - StoreSetting initialization with BusinessHours
 * - Image mapping (store_images)
 * - Owner-Store relationship
 * - Old image cleanup
 * - Cleanup of related entities (OrderItem, QrScanLog) on deletion
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Store::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Store::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Store::class)]
#[AsEntityListener(event: Events::preRemove, method: 'preRemove', entity: Store::class)]
class StoreSubscriber
{
    private ?int $previousImageId = null;

    public function __construct(
        private readonly MediaObjectService $mediaObjectService
    ) {}

    /**
     * Handle cleanup before removal
     */
    public function preRemove(Store $store, LifecycleEventArgs $event): void
    {
        $em = $event->getObjectManager();

        // 1. Unlink OrderItems (set store to null)
        $orderItems = $em->getRepository(OrderItem::class)->findBy(['store' => $store]);
        foreach ($orderItems as $item) {
            $item->setStore(null);
        }

        // 2. Unlink QrScanLogs
        $logs = $em->getRepository(QrScanLog::class)->findBy(['store' => $store]);
        foreach ($logs as $log) {
            $log->setStore(null);
        }
    }

    /**
     * Initialize StoreSetting and handle owner relationship before persisting (create)
     */
    public function prePersist(Store $store, PrePersistEventArgs $event): void
    {
        // Initialize StoreSetting if not already set
        if (!$store->getSetting()) {
            $storeSetting = new StoreSetting();
            $store->setSetting($storeSetting);
            
            // Persist StoreSetting and its BusinessHours
            $event->getObjectManager()->persist($storeSetting);
            $this->persistBusinessHoursIfNeeded($storeSetting, $event);
        }

        // Set store_images mapping for image if set
        if ($store->getImage() instanceof MediaObject && $store->getImage()->getMapping() === null) {
            $store->getImage()->setMapping('store_images');
        }

        // Link owner and store bidirectionally
        if ($store->getOwner() instanceof User) {
            $owner = $store->getOwner();
            $owner->setStore($store);
            
            // Ensure owner has ROLE_STORE
            $roles = $owner->getRoles();
            if (!in_array('ROLE_STORE', $roles)) {
                $roles[] = 'ROLE_STORE';
                $owner->setRoles($roles);
            }
        }
    }

    /**
     * Handle image mapping and owner relationship before updating
     */
    public function preUpdate(Store $store, PreUpdateEventArgs $event): void
    {
        // Store previous image ID for cleanup
        $this->previousImageId = $store->getImage()?->getId();

        // Set store_images mapping for image if set and mapping is not already set
        if ($store->getImage() instanceof MediaObject && $store->getImage()->getMapping() === null) {
            $store->getImage()->setMapping('store_images');
        }

        // Link owner and store bidirectionally
        if ($store->getOwner() instanceof User) {
            $owner = $store->getOwner();
            $owner->setStore($store);
            
            // Ensure owner has ROLE_STORE
            $roles = $owner->getRoles();
            if (!in_array('ROLE_STORE', $roles)) {
                $roles[] = 'ROLE_STORE';
                $owner->setRoles($roles);
            }
        }
    }

    /**
     * Clean up old image after update
     */
    public function postUpdate(Store $store, PostUpdateEventArgs $event): void
    {
        // Delete previous MediaObject if it was replaced
        $currentImageId = $store->getImage()?->getId();
        if ($this->previousImageId && $this->previousImageId !== $currentImageId) {
            $this->mediaObjectService->deleteMediaObjectsByIds([$this->previousImageId]);
        }
        
        // Reset for next update
        $this->previousImageId = null;
    }

    /**
     * Persist BusinessHours if they don't have IDs
     * Note: All BusinessHours are null by default, so this will only persist
     * BusinessHours that have been explicitly set
     */
    private function persistBusinessHoursIfNeeded(StoreSetting $setting, PrePersistEventArgs $event): void
    {
        $methods = [
            'getMondayHours',
            'getTuesdayHours',
            'getWednesdayHours',
            'getThursdayHours',
            'getFridayHours',
            'getSaturdayHours',
            'getSundayHours'
        ];

        foreach ($methods as $method) {
            $hours = $setting->$method();
            if ($hours && $hours->getId() === null) {
                $event->getObjectManager()->persist($hours);
            }
        }
    }
}

