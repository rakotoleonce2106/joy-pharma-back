<?php

namespace App\Form\DataTransformer;

use App\Entity\Location;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms empty Location objects to null
 * This prevents PropertyAccessor errors when location fields are empty
 */
class NullLocationTransformer implements DataTransformerInterface
{
    public function transform($location): ?Location
    {
        // Transform from model to view (Location entity to form)
        return $location;
    }

    public function reverseTransform($location): ?Location
    {
        // Transform from view to model (form to Location entity)
        // Handle case where location might be null or not an instance
        if (!$location instanceof Location) {
            // If form data was submitted but location is null, create a new Location
            // This handles the case where form starts with null empty_data
            return null;
        }

        // If all location fields are empty, return null instead of empty object
        $address = $location->getAddress();
        $latitude = $location->getLatitude();
        $longitude = $location->getLongitude();
        
        if (empty($address) && 
            ($latitude === null || $latitude === 0.0) && 
            ($longitude === null || $longitude === 0.0)) {
            return null;
        }

        return $location;
    }
}

