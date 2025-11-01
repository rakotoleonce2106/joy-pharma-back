<?php

namespace App\Form\DataTransformer;

use App\Entity\BusinessHours;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms null BusinessHours to empty BusinessHours objects
 * This prevents ReflectionObject errors when BusinessHours properties are null
 */
class NullBusinessHoursTransformer implements DataTransformerInterface
{
    public function transform($businessHours): ?BusinessHours
    {
        // Transform from model to view (BusinessHours entity to form)
        // If null, return an empty BusinessHours object
        if ($businessHours === null) {
            return new BusinessHours(null, null, false);
        }
        
        return $businessHours;
    }

    public function reverseTransform($businessHours): ?BusinessHours
    {
        // Transform from view to model (form to BusinessHours entity)
        // If null, return an empty BusinessHours object
        if ($businessHours === null) {
            return new BusinessHours(null, null, false);
        }
        
        return $businessHours;
    }
}

