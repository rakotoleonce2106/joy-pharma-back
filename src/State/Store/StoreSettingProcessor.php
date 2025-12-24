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
                
                // Get incoming values directly
                $incomingOpenTime = $incomingHours->getOpenTime();
                $incomingCloseTime = $incomingHours->getCloseTime();
                $incomingIsClosed = $incomingHours->isClosed();
                
                // Update properties directly as strings
                $existingHours->setOpenTime($incomingOpenTime);
                $existingHours->setCloseTime($incomingCloseTime);
                $existingHours->setIsClosed($incomingIsClosed);
                
                // Force Doctrine UnitOfWork to mark this entity as changed
                $this->entityManager->persist($existingHours);
                
                // Force Doctrine to detect changes by calling setter on parent
                $existing->$setter($existingHours);
                
                // Use Doctrine's UnitOfWork to explicitly recompute change set
                $unitOfWork = $this->entityManager->getUnitOfWork();
                $metadata = $this->entityManager->getClassMetadata(BusinessHours::class);
                $unitOfWork->recomputeSingleEntityChangeSet($metadata, $existingHours);
            }
        }
    }
}
