<?php

namespace App\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class CurrentUserProvider implements ProviderInterface
{
    public function __construct(
        private Security $security
    )
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('User not authenticated or not a valid User entity.');
        }

        // Build base user data matching auth response format
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
                    'name' => $store->getName() ?? null,
                    'description' => $store->getDescription() ?? null,
                    'isActive' => $user->getActive(),
                ];

                // Add contact info if exists
                $contact = $store->getContact();
                if ($contact) {
                    $storeData['phone'] = $contact->getPhone() ?? null;
                    $storeData['email'] = $contact->getEmail() ?? null;
                }

                // Add location info if exists
                $location = $store->getLocation();
                if ($location) {
                    $storeData['address'] = $location->getAddress() ?? null;
                    $storeData['city'] = $location->getCity() ?? null;
                    $storeData['latitude'] = $location->getLatitude() ?? null;
                    $storeData['longitude'] = $location->getLongitude() ?? null;
                }

                // Add image if exists
                if ($store->getImage() && $store->getImage()->getName()) {
                    $storeData['image'] = '/images/store/' . $store->getImage()->getName();
                }

                $userData['store'] = $storeData;
            }
        }

        return $userData;
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
