<?php

namespace App\State\Store;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\BusinessHours;
use App\Entity\StoreSetting;
use App\Entity\User;
use App\Repository\StoreSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreSettingProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StoreSettingRepository $storeSettingRepository,
        private readonly Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): StoreSetting
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('User not authenticated');
        }

        if (!$user->getStore()) {
            throw new NotFoundHttpException('User does not have an associated store');
        }

        // Get the existing StoreSetting from the database with all relationships loaded
        $existingStoreSetting = $user->getStore()->getSetting();

        if (!$existingStoreSetting) {
            throw new NotFoundHttpException('Store setting not found for your store');
        }

        // Ensure all BusinessHours are loaded and initialized (no refresh needed)
        foreach (['mondayHours', 'tuesdayHours', 'wednesdayHours', 'thursdayHours', 'fridayHours', 'saturdayHours', 'sundayHours'] as $prop) {
            $getter = 'get' . ucfirst($prop);
            $hours = $existingStoreSetting->$getter();
            // Force loading if not loaded
            if ($hours) {
                $this->entityManager->initializeObject($hours);
            }
        }

        // Update existing BusinessHours instead of replacing them
        $this->updateBusinessHours($existingStoreSetting, $data);

        // Persist the changes - explicitly persist the StoreSetting and all related BusinessHours
        $this->entityManager->persist($existingStoreSetting);
        
        // Ensure all updated BusinessHours are also persisted
        foreach (['mondayHours', 'tuesdayHours', 'wednesdayHours', 'thursdayHours', 'fridayHours', 'saturdayHours', 'sundayHours'] as $prop) {
            $getter = 'get' . ucfirst($prop);
            $hours = $existingStoreSetting->$getter();
            if ($hours) {
                $this->entityManager->persist($hours);
            }
        }
        
        // Flush changes to database
        $this->entityManager->flush();
        
        // Detach and reload the entity to ensure we return fresh data
        // This prevents stale data from being returned
        $storeSettingId = $existingStoreSetting->getId();
        $this->entityManager->detach($existingStoreSetting);
        
        // Reload from database to ensure we return the updated values
        $freshStoreSetting = $this->storeSettingRepository->find($storeSettingId);
        if (!$freshStoreSetting) {
            throw new NotFoundHttpException('Store setting not found after update');
        }

        return $freshStoreSetting;
    }

    private function updateBusinessHours(StoreSetting $existing, StoreSetting $incoming): void
    {
        $hoursMapping = [
            'mondayHours' => ['getMondayHours', 'setMondayHours'],
            'tuesdayHours' => ['getTuesdayHours', 'setTuesdayHours'],
            'wednesdayHours' => ['getWednesdayHours', 'setWednesdayHours'],
            'thursdayHours' => ['getThursdayHours', 'setThursdayHours'],
            'fridayHours' => ['getFridayHours', 'setFridayHours'],
            'saturdayHours' => ['getSaturdayHours', 'setSaturdayHours'],
            'sundayHours' => ['getSundayHours', 'setSundayHours'],
        ];

        foreach ($hoursMapping as $property => $methods) {
            $getter = $methods[0];
            $setter = $methods[1];
            $incomingHours = $incoming->$getter();
            
            // Only process if incoming hours data is provided (partial updates are supported)
            if ($incomingHours !== null) {
                $existingHours = $existing->$getter();
                
                // If existing hours don't exist, create new ones
                if ($existingHours === null) {
                    $existingHours = new BusinessHours(null, null, false);
                    $existing->$setter($existingHours);
                    $this->entityManager->persist($existingHours);
                }
                
                // Get incoming values directly (API Platform should have already deserialized them)
                // Note: API Platform might use the constructor which might not set null values correctly
                // So we need to check if values were actually set by API Platform
                $incomingOpenTime = $incomingHours->getOpenTime();
                $incomingCloseTime = $incomingHours->getCloseTime();
                $incomingIsClosed = $incomingHours->isClosed();
                
                // If API Platform used the constructor and isClosed is true, it might not have set openTime/closeTime
                // So we need to trust the incoming object state - if it has null, that's what we want
                
                // Handle time conversion - API Platform might send strings that need conversion
                // If it's already a DateTime, use it; if string, parse it; if null, keep null
                $finalOpenTime = null;
                $finalCloseTime = null;
                
                if ($incomingOpenTime !== null) {
                    if (is_string($incomingOpenTime)) {
                        // Convert "09:00" or "09:00:00" format to DateTime
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
                // If null, $finalOpenTime stays null
                
                if ($incomingCloseTime !== null) {
                    if (is_string($incomingCloseTime)) {
                        // Convert "09:00" or "09:00:00" format to DateTime
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
                // If null, $finalCloseTime stays null
                
                // Always update all properties (including null values) - PUT updates all fields
                // Set the values explicitly
                $existingHours->setOpenTime($finalOpenTime);
                $existingHours->setCloseTime($finalCloseTime);
                $existingHours->setIsClosed($incomingIsClosed ?? false);
                
                // Force Doctrine UnitOfWork to mark this entity as changed
                // This ensures Doctrine detects changes to null values
                $this->entityManager->persist($existingHours);
                
                // Force Doctrine to detect changes by calling setter on parent
                // This ensures Doctrine tracks the relationship change
                $existing->$setter($existingHours);
                
                // Use Doctrine's UnitOfWork to explicitly recompute change set
                // This forces Doctrine to detect null value changes
                $unitOfWork = $this->entityManager->getUnitOfWork();
                $metadata = $this->entityManager->getClassMetadata(BusinessHours::class);
                $unitOfWork->recomputeSingleEntityChangeSet($metadata, $existingHours);
            }
        }
    }
}
