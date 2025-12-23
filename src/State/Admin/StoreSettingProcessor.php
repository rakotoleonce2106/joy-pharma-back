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
        $days = ['mondayHours', 'tuesdayHours', 'wednesdayHours', 'thursdayHours', 'fridayHours', 'saturdayHours', 'sundayHours'];

        foreach ($days as $day) {
            $getter = 'get' . ucfirst($day);
            $setter = 'set' . ucfirst($day);
            
            $incomingHours = $data->$getter();
            $existingHours = $existingStoreSetting->$getter();

            // Only update if incoming hours are provided (not null)
            if ($incomingHours !== null) {
                if ($existingHours === null) {
                    // Create new BusinessHours if it doesn't exist
                    $newHours = new BusinessHours(
                        $incomingHours->getOpenTime() ? $incomingHours->getOpenTime()->format('H:i') : null,
                        $incomingHours->getCloseTime() ? $incomingHours->getCloseTime()->format('H:i') : null,
                        $incomingHours->isClosed()
                    );
                    $existingStoreSetting->$setter($newHours);
                    $this->entityManager->persist($newHours);
                } else {
                    // Update existing BusinessHours properties
                    $existingHours->setOpenTime($incomingHours->getOpenTime());
                    $existingHours->setCloseTime($incomingHours->getCloseTime());
                    $existingHours->setIsClosed($incomingHours->isClosed());
                    // Keep the existing BusinessHours object (don't replace it)
                    $existingStoreSetting->$setter($existingHours);
                    $this->entityManager->persist($existingHours);
                }
            }
            // If incomingHours is null, do nothing - keep existing hours unchanged
        }

        // Persist and flush
        $this->entityManager->persist($existingStoreSetting);
        $this->entityManager->flush();

        return $existingStoreSetting;
    }
}

