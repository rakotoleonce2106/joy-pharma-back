<?php

namespace App\Serializer;

use App\Entity\BusinessHours;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

final class BusinessHoursNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'BUSINESS_HOURS_NORMALIZER_ALREADY_CALLED';

    public function normalize($object, ?string $format = null, array $context = []): array
    {
        $context[self::ALREADY_CALLED] = true;

        // Normalize using the default normalizer first
        $data = $this->normalizer->normalize($object, $format, $context);

        if (!is_array($data)) {
            return $data;
        }

        // Replace openTime/closeTime DateTime objects with formatted strings (HH:mm format)
        // The normalizer will convert DateTime to string format
        
        // Handle openTime - replace DateTime with formatted string
        if (isset($data['openTime'])) {
            if ($data['openTime'] instanceof \DateTimeInterface) {
                // Convert DateTime to HH:mm format
                $data['openTime'] = $data['openTime']->format('H:i');
            } elseif (is_string($data['openTime'])) {
                // Handle ISO datetime strings like "1970-01-01T08:00:00+00:00"
                if (preg_match('/^\d{4}-\d{2}-\d{2}T(\d{2}:\d{2})/', $data['openTime'], $matches)) {
                    $data['openTime'] = $matches[1];
                }
                // If already in HH:mm format, keep it as is
            }
        }
        
        // Use openTimeString if available and openTime is not set or is DateTime
        if (isset($data['openTimeString']) && (!isset($data['openTime']) || $data['openTime'] instanceof \DateTimeInterface)) {
            $data['openTime'] = $data['openTimeString'];
        }
        if (isset($data['openTimeString'])) {
            unset($data['openTimeString']);
        }

        // Handle closeTime - replace DateTime with formatted string
        if (isset($data['closeTime'])) {
            if ($data['closeTime'] instanceof \DateTimeInterface) {
                // Convert DateTime to HH:mm format
                $data['closeTime'] = $data['closeTime']->format('H:i');
            } elseif (is_string($data['closeTime'])) {
                // Handle ISO datetime strings like "1970-01-01T17:00:00+00:00"
                if (preg_match('/^\d{4}-\d{2}-\d{2}T(\d{2}:\d{2})/', $data['closeTime'], $matches)) {
                    $data['closeTime'] = $matches[1];
                }
                // If already in HH:mm format, keep it as is
            }
        }
        
        // Use closeTimeString if available and closeTime is not set or is DateTime
        if (isset($data['closeTimeString']) && (!isset($data['closeTime']) || $data['closeTime'] instanceof \DateTimeInterface)) {
            $data['closeTime'] = $data['closeTimeString'];
        }
        if (isset($data['closeTimeString'])) {
            unset($data['closeTimeString']);
        }

        return $data;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        // Skip if already called to prevent infinite recursion
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof BusinessHours;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            BusinessHours::class => true,
        ];
    }
}

