<?php

namespace App\Security;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JwtAuthenticationSuccessHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Build base user data
        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'phone' => $user->getPhone(),
            'roles' => $user->getRoles(),
            'userType' => $this->getUserType($user),
            'isActive' => $user->getActive(),
        ];

        // Add image if exists
        if ($user->getImage() && $user->getImage()->getName()) {
            $userData['avatar'] = '/uploads/profile/' . $user->getImage()->getName();
        }

        // Add role-specific data for DELIVERY PERSONS
        if (in_array('ROLE_DELIVER', $user->getRoles())) {
            $delivery = $user->getDelivery();
            if ($delivery) {
            $userData['delivery'] = [
                    'vehicleType' => $delivery->getVehicleType(),
                    'vehiclePlate' => $delivery->getVehiclePlate(),
                    'isOnline' => $delivery->getIsOnline(),
                    'totalDeliveries' => $delivery->getTotalDeliveries(),
                    'averageRating' => $delivery->getAverageRating(),
                    'totalEarnings' => $delivery->getTotalEarnings(),
                    'currentLatitude' => $delivery->getCurrentLatitude(),
                    'currentLongitude' => $delivery->getCurrentLongitude(),
                    'lastLocationUpdate' => $delivery->getLastLocationUpdate()?->format('c'),
            ];
            }
        }

        // Add role-specific data for STORE OWNERS
        if (in_array('ROLE_STORE', $user->getRoles())) {
            $store = $user->getStore();
            if ($store) {
                $storeData = [
                    'id' => $store->getId(),
                    'name' => $store->getName(),
                    'description' => $store->getDescription(),
                    'isActive' => $user->getActive(),
                ];

                // Add contact info if exists
                $contact = $store->getContact();
                if ($contact) {
                    $storeData['phone'] = $contact->getPhone();
                    $storeData['email'] = $contact->getEmail();
                }

                // Add location info if exists
                $location = $store->getLocation();
                if ($location) {
                    $storeData['address'] = $location->getAddress();
                    $storeData['city'] = $location->getCity();
                    $storeData['latitude'] = $location->getLatitude();
                    $storeData['longitude'] = $location->getLongitude();
                }

                // Add image if exists
                if ($store->getImage() && $store->getImage()->getName()) {
                    $storeData['image'] = '/images/store/' . $store->getImage()->getName();
                }

                $userData['store'] = $storeData;
            }
        }

        // Add user data to JWT response
        $data['user'] = $userData;
        
        $event->setData($data);
    }

    /**
     * Determine the primary user type based on roles
     */
    private function getUserType(User $user): string
    {
        $roles = $user->getRoles();
        
        // Priority order: admin > store > delivery > customer
        if (in_array('ROLE_ADMIN', $roles)) {
            return 'admin';
        }
        
        if (in_array('ROLE_STORE', $roles)) {
            return 'store';
        }
        
        if (in_array('ROLE_DELIVER', $roles)) {
            return 'delivery';
        }
        
        return 'customer';
    }
}

