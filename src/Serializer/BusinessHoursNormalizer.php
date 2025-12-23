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
        // Convert DateTime objects or ISO datetime strings to simple "HH:mm" format
        
        // Handle openTime
        if (isset($data['openTime'])) {
            if ($data['openTime'] instanceof \DateTimeInterface) {
                // Convert DateTime to HH:mm format
                $data['openTime'] = $data['openTime']->format('H:i');
            } elseif (is_string($data['openTime'])) {
                // Handle ISO datetime strings like "1970-01-01T08:00:00+00:00" or "1970-01-01T08:00:00Z"
                if (preg_match('/^\d{4}-\d{2}-\d{2}T(\d{2}:\d{2})(?::\d{2})?/', $data['openTime'], $matches)) {
                    $data['openTime'] = $matches[1];
                }
                // If already in HH:mm format, keep it as is
            }
        } elseif (isset($data['openTimeString'])) {
            // Fallback to openTimeString if openTime is not set
            $data['openTime'] = $data['openTimeString'];
            unset($data['openTimeString']);
        }

        // Handle closeTime
        if (isset($data['closeTime'])) {
            if ($data['closeTime'] instanceof \DateTimeInterface) {
                // Convert DateTime to HH:mm format
                $data['closeTime'] = $data['closeTime']->format('H:i');
            } elseif (is_string($data['closeTime'])) {
                // Handle ISO datetime strings like "1970-01-01T17:00:00+00:00" or "1970-01-01T17:00:00Z"
                if (preg_match('/^\d{4}-\d{2}-\d{2}T(\d{2}:\d{2})(?::\d{2})?/', $data['closeTime'], $matches)) {
                    $data['closeTime'] = $matches[1];
                }
                // If already in HH:mm format, keep it as is
            }
        } elseif (isset($data['closeTimeString'])) {
            // Fallback to closeTimeString if closeTime is not set
            $data['closeTime'] = $data['closeTimeString'];
            unset($data['closeTimeString']);
        }
        
        // Clean up any remaining string properties
        if (isset($data['openTimeString'])) {
            unset($data['openTimeString']);
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

