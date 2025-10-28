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
        if (!$location instanceof Location) {
            return null;
        }

        // If all location fields are empty, return null instead of empty object
        if (empty($location->getAddress()) && 
            ($location->getLatitude() === null || $location->getLatitude() === 0.0) && 
            ($location->getLongitude() === null || $location->getLongitude() === 0.0)) {
            return null;
        }

        return $location;
    }
}

