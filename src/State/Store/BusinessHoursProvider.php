<?php

namespace App\State\Store;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\BusinessHoursResponse;
use App\Repository\StoreRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BusinessHoursProvider implements ProviderInterface
{
    public function __construct(
        private readonly StoreRepository $storeRepository,
        private readonly Security $security
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        // Get the authenticated user
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('Authentication required');
        }

        // Find the store owned by this user
        $store = $this->storeRepository->findOneBy(['owner' => $user]);

        if (!$store) {
            throw new NotFoundHttpException('No store found for this user');
        }

        $setting = $store->getSetting();
        
        if (!$setting) {
            throw new NotFoundHttpException('Store settings not found');
        }

        // Build hours array for each day
        $hours = [
            'monday' => $this->formatBusinessHours($setting->getMondayHours()),
            'tuesday' => $this->formatBusinessHours($setting->getTuesdayHours()),
            'wednesday' => $this->formatBusinessHours($setting->getWednesdayHours()),
            'thursday' => $this->formatBusinessHours($setting->getThursdayHours()),
            'friday' => $this->formatBusinessHours($setting->getFridayHours()),
            'saturday' => $this->formatBusinessHours($setting->getSaturdayHours()),
            'sunday' => $this->formatBusinessHours($setting->getSundayHours()),
        ];

        // Determine if currently open
        $now = new \DateTime();
        $currentDay = strtolower($now->format('l'));
        $todayHours = $setting->{'get' . ucfirst($currentDay) . 'Hours'}();
        
        $isCurrentlyOpen = false;
        if ($todayHours && !$todayHours->isClosed()) {
            $isCurrentlyOpen = $todayHours->isOpen($now);
        }

        // Calculate next open time
        $nextOpenTime = $this->calculateNextOpenTime($setting, $now);

        return new BusinessHoursResponse(
            storeId: $store->getId(),
            storeName: $store->getName(),
            hours: $hours,
            isCurrentlyOpen: $isCurrentlyOpen,
            nextOpenTime: $nextOpenTime
        );
    }

    private function formatBusinessHours($businessHours): array
    {
        if (!$businessHours) {
            return [
                'isClosed' => true,
                'openTime' => null,
                'closeTime' => null,
                'formatted' => 'Closed'
            ];
        }

        if ($businessHours->isClosed()) {
            return [
                'isClosed' => true,
                'openTime' => null,
                'closeTime' => null,
                'formatted' => 'Closed'
            ];
        }

        $openTime = $businessHours->getOpenTime();
        $closeTime = $businessHours->getCloseTime();

        return [
            'isClosed' => false,
            'openTime' => $openTime ? $openTime->format('H:i') : null,
            'closeTime' => $closeTime ? $closeTime->format('H:i') : null,
            'formatted' => $businessHours->getFormattedHours()
        ];
    }

    private function calculateNextOpenTime($setting, \DateTime $now): ?string
    {
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $currentDayIndex = (int) $now->format('N') - 1; // Monday = 0
        
        // Check next 7 days
        for ($i = 0; $i < 7; $i++) {
            $dayIndex = ($currentDayIndex + $i) % 7;
            $dayName = $daysOfWeek[$dayIndex];
            $hours = $setting->{'get' . ucfirst($dayName) . 'Hours'}();
            
            if (!$hours || $hours->isClosed()) {
                continue;
            }
            
            $openTime = $hours->getOpenTime();
            if (!$openTime) {
                continue;
            }
            
            // For today, check if opening time is in the future
            if ($i === 0) {
                $todayOpenTime = clone $now;
                $todayOpenTime->setTime(
                    (int) $openTime->format('H'),
                    (int) $openTime->format('i')
                );
                
                if ($todayOpenTime > $now) {
                    return ucfirst($dayName) . ' at ' . $openTime->format('H:i');
                }
            } else {
                // Return the next day that's open
                return ucfirst($dayName) . ' at ' . $openTime->format('H:i');
            }
        }
        
        return null;
    }
}

