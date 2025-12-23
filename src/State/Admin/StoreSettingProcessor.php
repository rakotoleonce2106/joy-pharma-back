<?php

namespace App\State\Admin;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\BusinessHours;
use App\Entity\StoreSetting;
use App\Repository\StoreSettingRepository;
use Doctrine\ORM\EntityManagerInterface;

class StoreSettingProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly StoreSettingRepository $storeSettingRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StoreSetting
    {
        if (!$data instanceof StoreSetting) {
            throw new \InvalidArgumentException('Expected StoreSetting entity');
        }

        $storeSettingId = $uriVariables['id'] ?? null;
        if (!$storeSettingId) {
            throw new \InvalidArgumentException('Store setting ID is required');
        }

        // Load existing StoreSetting from database
        $existingStoreSetting = $this->storeSettingRepository->find($storeSettingId);
        if (!$existingStoreSetting) {
            throw new \RuntimeException('Store setting not found');
        }

        // Update only the BusinessHours that are provided in the request
        // Use reflection to access private properties directly, avoiding getters that create defaults
        $reflection = new \ReflectionClass(StoreSetting::class);
        
        $days = ['mondayHours', 'tuesdayHours', 'wednesdayHours', 'thursdayHours', 'fridayHours', 'saturdayHours', 'sundayHours'];

        foreach ($days as $day) {
            $property = $reflection->getProperty($day);
            $property->setAccessible(true);
            
            $setter = 'set' . ucfirst($day);
            $existingGetter = 'get' . ucfirst($day);
            
            // Access incoming hours directly via reflection to avoid getter side effects
            $incomingHours = $property->getValue($data);
            $existingHours = $existingStoreSetting->$existingGetter();

            // Only update if incoming hours are provided (not null)
            if ($incomingHours !== null) {
                // Check if the existing BusinessHours is shared with other days
                $isShared = false;
                if ($existingHours !== null && $existingHours->getId() !== null) {
                    foreach ($days as $otherDay) {
                        if ($otherDay !== $day) {
                            $otherGetter = 'get' . ucfirst($otherDay);
                            $otherHours = $existingStoreSetting->$otherGetter();
                            if ($otherHours !== null && $otherHours->getId() === $existingHours->getId()) {
                                $isShared = true;
                                break;
                            }
                        }
                    }
                }
                
                // If shared or null, create a new instance for this day
                if ($existingHours === null || $isShared) {
                    $existingHours = new BusinessHours(null, null, false);
                    $existingStoreSetting->$setter($existingHours);
                    $this->entityManager->persist($existingHours);
                }
                
                // Get incoming values
                $incomingOpenTime = $incomingHours->getOpenTime();
                $incomingCloseTime = $incomingHours->getCloseTime();
                $incomingIsClosed = $incomingHours->isClosed();
                
                // Handle time conversion - API Platform might send strings that need conversion
                // If it's already a DateTime, use it; if string, parse it; if null, keep null
                $finalOpenTime = null;
                $finalCloseTime = null;
                
                if ($incomingOpenTime !== null) {
                    if (is_string($incomingOpenTime)) {
                        // Convert "08:00" or "08:00:00" format to DateTime
                        try {
                            $parsedTime = \DateTime::createFromFormat('H:i', $incomingOpenTime);
                            if ($parsedTime === false) {
                                $parsedTime = \DateTime::createFromFormat('H:i:s', $incomingOpenTime);
                            }
                            if ($parsedTime === false) {
                                // Try parsing as full datetime string
                                try {
                                    $parsedTime = new \DateTime($incomingOpenTime);
                                } catch (\Exception $e) {
                                    $parsedTime = null;
                                }
                            }
                            $finalOpenTime = $parsedTime !== false ? $parsedTime : null;
                        } catch (\Exception $e) {
                            $finalOpenTime = null;
                        }
                    } else {
                        // Already a DateTime object
                        $finalOpenTime = $incomingOpenTime;
                    }
                }
                
                if ($incomingCloseTime !== null) {
                    if (is_string($incomingCloseTime)) {
                        // Convert "17:00" or "17:00:00" format to DateTime
                        try {
                            $parsedTime = \DateTime::createFromFormat('H:i', $incomingCloseTime);
                            if ($parsedTime === false) {
                                $parsedTime = \DateTime::createFromFormat('H:i:s', $incomingCloseTime);
                            }
                            if ($parsedTime === false) {
                                // Try parsing as full datetime string
                                try {
                                    $parsedTime = new \DateTime($incomingCloseTime);
                                } catch (\Exception $e) {
                                    $parsedTime = null;
                                }
                            }
                            $finalCloseTime = $parsedTime !== false ? $parsedTime : null;
                        } catch (\Exception $e) {
                            $finalCloseTime = null;
                        }
                    } else {
                        // Already a DateTime object
                        $finalCloseTime = $incomingCloseTime;
                    }
                }
                
                // Always update all properties (including null values) - PUT updates all fields
                $existingHours->setOpenTime($finalOpenTime);
                $existingHours->setCloseTime($finalCloseTime);
                $existingHours->setIsClosed($incomingIsClosed ?? false);
                
                // Force Doctrine UnitOfWork to mark this entity as changed
                $this->entityManager->persist($existingHours);
                
                // Force Doctrine to detect changes by calling setter on parent
                $existingStoreSetting->$setter($existingHours);
                
                // Use Doctrine's UnitOfWork to explicitly recompute change set
                $unitOfWork = $this->entityManager->getUnitOfWork();
                $metadata = $this->entityManager->getClassMetadata(BusinessHours::class);
                $unitOfWork->recomputeSingleEntityChangeSet($metadata, $existingHours);
            }
            // If incomingHours is null, do nothing - keep existing hours unchanged
        }

        // Persist and flush
        $this->entityManager->persist($existingStoreSetting);
        $this->entityManager->flush();

        return $existingStoreSetting;
    }
}

